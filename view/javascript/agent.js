class Devent {
  constructor() {
    let _this = this;
    this.promise = new Promise(function (resolve, reject) {
      _this.fire_event = function (data) {
        // console.log("fire_event");
        // console.log(data);
        resolve(data);
      };
      _this.fire_error_event = function (error) {
        // console.log("fire_error_event");
        // console.log(error);
        reject(error);
      };
    });
  }
}

class Process {
  constructor(proxy, module_name, proc, data) {
    this.status = "new";
    this.proxy = proxy;
    this.module_name = module_name;
    this.proc = proc;
    this.events_map = new Map();
    this.callbacks = new Map();
    this.proc_uid = ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
    this.proc_data = data;
    this.timeout_timer = null;
    this.timeout = 150000;
    this.server_name = window.location.hostname;

  }

  addCallback(event_name, callback) {
    this.callbacks.set(event_name, callback);
  }

  run() {
    let _this = this;
    if (this.status != "new") {
      this.callbacks.get("error")({ "error": "process was started earlier" });
      return;
    }
    this.proxy.check_ws().then(
      function (result) {
        let data = {
          "module": _this.module_name,
          "method": _this.proc,
          "procUID": _this.proc_uid,
          "serverName": _this.server_name,
          "data": _this.proc_data
        };
        //let devent = new Devent();
        _this.proxy.add_proc(_this.proc_uid, _this);
        _this.proxy.websocket.send(JSON.stringify(data));
        _this.status = "started";
        _this.timeout_timer = setTimeout(function () {
          _this.callbacks.get("error")({ "error": "process timeout excided", "error_code": "5" });
          _this.status = "error";
        }, _this.timeout);
        //return devent.promise;
      },
      function (error) {
        // console.log("errror: " + error);
        if (_this.callbacks.get("error")) {
          _this.callbacks.get("error")({ "error": "no connection to agent", "error_code": "1" });
        }
        _this.status = "error";

      });


  }

  cancel() {
    let _this = this;
    if (_this.status === "started") {
      clearTimeout(this.timeout_timer);

      let data = {
        "module": _this.module_name,
        "method": "cancel",
        "procUID": _this.proc_uid,
        "data": {}
      };
      _this.proxy.websocket.send(JSON.stringify(data));
      _this.status = "canceling";
      _this.timeout_timer = setTimeout(function () {
        _this.callbacks.get("error")({ "error": "process timeout excided", "error_code": "5" });
        _this.status = "error";
      }, _this.timeout);
    }
  }

  runCallback(callback_name, data) {
    clearTimeout(this.timeout_timer);
    let _this = this;
    switch (callback_name) {
      case "complete":
        this.status = "complete";
        this.proxy.remove_proc(this.proc_id);
        break;
      case "canceled":
        if (this.callbacks.get(callback_name)) {
          this.callbacks.get(callback_name)(data);
        }
        this.status = "canceled";
        this.proxy.remove_proc(this.proc_id);
        break;
      default:
        if (this.callbacks.get(callback_name)) {
          this.callbacks.get(callback_name)(data);
        }
        this.timeout_timer = setTimeout(function () {
          _this.callbacks.get("error")({ "error": "process timeout excided", "error_code": "5" });
          _this.status = "error";
        }, _this.timeout);
    }
  }
}

class AgentProxy {
  constructor(addr) {
    this.websocket_addr = addr;
    this.avaliable_modules = [];
    this.websocket = null;
    this.state = "disconnected";
    this.message_id = 0;
    this.message_map = new Map();
    this.callbacks = new Map();
    this.onclosecallbacks = new Map();
    this.action_promise = null;
    this.events_map = new Map();
    this.watching_files = new Map();
    this.processes = new Map();
    let _this = this;
    this.get_avaliablie_modules().then(function (result) {
      _this.avaliable_modules = result.modules;
    }, function (error) {
      // console.log("AP error (getavaliabemodules)!");
    });
  }
  add_proc(proc_id, proc) {
    this.processes.set(proc_id, proc);
  }
  remove_proc(proc_id) {
    this.processes.delete(proc_id);
  }

  check_ws() {
    let _this = this;
    let promise = new Promise(function (resolve, reject) {
      if (_this.state === "disconnected") {
        _this.state = "connecting";
        _this.websocket = new WebSocket(_this.websocket_addr);

        _this.websocket.onerror = function (event) {
          _this.state = "disconnected";
          reject("agent connection error");
        };
        _this.websocket.onopen = function (event) {
          _this.state = "connected";
          resolve();
        };
        _this.websocket.onclose = function (event) {
          if (event.wasClean) {
            // console.log('connection has been closed');
          } else {
            // console.log('Connection error');
          }
          // console.log('Code: ' + event.code + ' Reason: ' + event.reason);
          _this.state = "disconnected";
          reject("agent connection error");
        };
        _this.websocket.onmessage = function (event) {
          let data = JSON.parse(event.data);
          if (data != null) {
            if (data['procUID']) {
              let proc = _this.processes.get(data['procUID']);
              if (proc && data['event'])
                proc.runCallback(data['event'], data);

            }

            /*if (data['errorCode'] === "NONE") {
             if (data['requestId']) {
             _this.events_map.get(data['requestId']).fire_event(data);
             _this.events_map.delete(data['requestId']);
             } else {
             
             }
             } else {
             if (data['requestId']) {
             _this.events_map.get(data['requestId']).fire_error_event(data);
             _this.events_map.delete(data['requestId']);
             } else {
             //сообщение инициированное сервером
             if (data['module'] && data['UID']) {
             let module_name = data['module'];
             let uid = data['UID'];
             let callbacks_map = _this.callbacks.get(module_name);
             if (callbacks_map) {
             callbacks_map.get(uid)(data);
             }
             }
             }
             }*/
          }
        };
      } else if (_this.state === "connected") {
        resolve();
      } else if (_this.state === "connecting") {
        // setTimeout(function wait_check() { // откл
        //   switch (_this.state) {
        //     case "connecting":
        //       setTimeout(wait_check, 1000);
        //       break;
        //     case "connected":
        //       resolve();
        //       break;
        //     case "disconnected":
        //       reject("agent connection error");

        //   }

        // }, 1000);
      }
    });
    return promise;
  }

  get_avaliablie_modules() {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "",
          "method": "getModules",
          "requestId": _this.message_id + "",
          "data": {}
        };
        let devent = new Devent();
        _this.events_map.set(_this.message_id + "", devent);
        _this.message_id++;
        _this.websocket.send(JSON.stringify(data));
        return devent.promise;
      },
      function (error) {
        return Promise.reject("no connection");
      }
    );
  }

  attachment_edit(fileurl, fielduid, fileuid, filename) {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "attachment_manager",
          "method": "edit_file",
          "requestId": _this.message_id + "",
          "data": {
            "url": fileurl,
            "fileUID": fileuid,
            "filename": filename,
            "UID": fielduid
          }
        };
        let devent = new Devent();
        _this.events_map.set(_this.message_id + "", devent);
        _this.message_id++;
        _this.websocket.send(JSON.stringify(data));
        return devent.promise;
      },
      function (error) {
        //var msg = "errror: " + error;
        return Promise.reject(error);
      });
  }

  attachment_edit_cancel(fielduid, fileuid) {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "attachment_manager",
          "method": "cancel_edit_file",
          "requestId": _this.message_id + "",
          "data": {
            "UID": fielduid,
            "FileUID": fileuid
          }
        };
        let devent = new Devent();
        _this.events_map.set(_this.message_id + "", devent);
        _this.message_id++;
        _this.websocket.send(JSON.stringify(data));
        return devent.promise;
      },
      function (error) {
        //var msg = "errror: " + error;
        return Promise.reject(error);
      });
  }

  getProcUID(modulename) {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "",
          "method": "getProcUID",
          "data": {}
        };
      }
    );
  }

  addCallback(modulename, uid, callback) {
    //console.log(this);
    let module_callbacks = this.callbacks.get(modulename);
    if (!module_callbacks) {
      module_callbacks = new Map();
      this.callbacks.set(modulename, module_callbacks);
    }
    module_callbacks.set(uid, callback);
  }

  addOnCloseCallback(modulename, callback) {
    //console.log(this);
    let module_callback = this.callbacks.get(modulename);
    if (!module_callback) {
      this.onclosecallbacks.set(modulename, callback);
    }
  }
  removeOnCloseCallback(modulename) {
    this.onclosecallbacks.delete(modulename);
  }

  set_notification_token_url(url) {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "notificator",
          "method": "set_notifications_token_url",
          "requestId": _this.message_id + "",
          "data": {
            "url": url
          }
        };
        let devent = new Devent();
        _this.events_map.set(_this.message_id + "", devent);
        _this.message_id++;
        _this.websocket.send(JSON.stringify(data));
        return devent.promise;
      },
      function (error) {
        //var msg = "errror: " + error;
        return Promise.reject(error);
      });
  }

  get_notifications_token_url() {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "notificator",
          "method": "get_notifications_token_url",
          "requestId": _this.message_id + "",
          "data": {
          }
        };
        let devent = new Devent();
        _this.events_map.set(_this.message_id + "", devent);
        _this.message_id++;
        _this.websocket.send(JSON.stringify(data));
        return devent.promise;
      },
      function (error) {
        //var msg = "errror: " + error;
        return Promise.reject(error);
      });
  }

  ncasign_file(fileurl, uploadurl) {
    let _this = this;
    return _this.check_ws().then(
      function (result) {
        let data = {
          "module": "ncasign_manager",
          "method": "sign_file",
          "requestId": _this.message_id + "",
          "data": {
            "url": fileurl,
            "upload_url": uploadurl
          }
        };
        let devent = new Devent();
        _this.events_map.set(_this.message_id + "", devent);
        _this.message_id++;
        _this.websocket.send(JSON.stringify(data));
        return devent.promise;
      },
      function (error) {
        //var msg = "errror: " + error;
        return Promise.reject(error);
      });
  }

}





function getCookie(name) {
  var matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
}

let dp = new AgentProxy('ws://127.0.0.1:34340/');

/*let t_proc = new Process(null, "test_module", "test_proc");
 console.log(t_proc);*/
