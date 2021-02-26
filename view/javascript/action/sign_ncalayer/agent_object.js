function agent_object() {
    this.webSocket = null;
    this._wserrorcallback = null;
    this._wsnoerrorcallback = null;
    this.setErrorCallback = function (callback) {
        this._wserrorcallback = callback;
    }
    this.setNoErrorCallback = function (callback) {
        this._wsnoerrorcallback = callback;
    }

    this.heartbeat_msg = '--heartbeat--';
    this.heartbeat_interval = null;
    this.missed_heartbeats = 0;
    const missed_heartbeats_limit_min = 3;
    const missed_heartbeats_limit_max = 50;
    this.missed_heartbeats_limit = missed_heartbeats_limit_min;
    this.callback = null;

    this.initialize = function () {
        var _this = this;
        this.webSocket = new WebSocket('ws://127.0.0.1:34340/');

        this.webSocket.onopen = function (event) {
            if (_this.heartbeat_interval === null) {
                _this.missed_heartbeats = 0;
                _this.heartbeat_interval = setInterval(this.pingLayer, 2000);
            }
            _this._wsnoerrorcallback();
            console.log("Connection opened");
        };

        this.webSocket.onclose = function (event) {
            if (event.wasClean) {
                console.log('connection has been closed');
            } else {
                console.log('Connection error');
                _this._wserrorcallback();
            }
            console.log('Code: ' + event.code + ' Reason: ' + event.reason);
        }

        this.webSocket.onmessage = function (event) {
            if (event.data === _this.heartbeat_msg) {
                _this.missed_heartbeats = 0;
                return;
            }
            var result = event.data;
            var file_path = event.data;
            console.log(file_path);

            if (result != null) {
                window[_this.callback](result);
            }
            
            //console.log(event);
            setMissedHeartbeatsLimitToMin();
        };
    };


    function setMissedHeartbeatsLimitToMax() {
        this.missed_heartbeats_limit = missed_heartbeats_limit_max;
    }

    function setMissedHeartbeatsLimitToMin() {
        this.missed_heartbeats_limit = missed_heartbeats_limit_min;
    }

    this.pingLayer = function () {
        var _this = this.
            console.log("pinging...");
        try {
            _this.missed_heartbeats++;
            if (_this.missed_heartbeats >= _this.missed_heartbeats_limit)
                throw new Error("Too many missed heartbeats.");
            _this.webSocket.send(_this.heartbeat_msg);
        } catch (e) {
            clearInterval(this.heartbeat_interval);
            _this.heartbeat_interval = null;
            console.warn("Closing connection. Reason: " + e.message);
            _this.webSocket.close();
        }
    }
    this.downloadFile = function(url, filename, ocsessid, callBack) {
        this.callback = callBack;
        var downloadFile = {
            "module" : "signfile_ncalayer",
            "method" : "downloadFile",
            "data" : {
                "url" : url,
                "filename" : filename,
                "OCSESSID" : ocsessid
            }
        }
        this.webSocket.send(JSON.stringify(downloadFile));
    }
}
