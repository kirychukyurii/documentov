/* Нотификатор отвечает за отображение виджета уведомлений
 * Верстка основной страницы требует отображение виджета в различных местах шаблона, в зависимости от размеров основного окна.
 * Соответственно, нотификатор разделен непосредственно на виджет уведомлений и на модель уведомлений.
 * На странице может находится несколько виджетов, которые могут быть связаны с одной или несколькими моделями уведомлений. 
 * Виджет реализован как стандартный плагин JQuery.
 * Модель связывается с виджетом через параметры виджета
 * */


function NoitficatorModel(params) {
  var _this = this;
  const favicon = new Image();  // Создание нового объекта изображения
  favicon.src = 'image/fv16.png';
  const faviconT = new Image();  // Создание нового объекта изображения
  faviconT.src = 'image/fv16t.png';



  _this.getCount = function () {
    return +localStorage.getItem('notificationCount') || 0;
  };
  _this.setCount = function (count) {
    localStorage.setItem('notificationCount', count);
  };
  if (params.polling_period) {
    if (typeof (params.polling_period) == 'string') {
      this.polling_period = parseInt(params.polling_period);
    } else if ((typeof (params.polling_period) == 'number')) {
      this.polling_period = params.polling_period;
    } else {
      this.polling_period = 10;
    }
  } else {

  }
  this.update_callbacks = [];
  this.registerUpdateCallback = function (callback) {
    _this.update_callbacks.push(callback);
  };

  this.tick = 0;
  this.max_tick = Math.ceil(10800 / _this.polling_period);
  this.notification_count_token_url = "";

  // this.agent_notificator_initialization = function () {
  //   if (dp) {
  //     dp.removeOnCloseCallback("notificator");
  //     dp.get_notifications_token_url().then(function (result) {
  //       if (!result['notifications_token_url']) {
  //         var url = 'index.php?route=common/notification/get_new_notifications_token';
  //         $.ajax({
  //           url: url,
  //           dataType: 'text'
  //         }).done(function (data) {
  //           if (data) {
  //             let href = window.location.href;
  //             let url = href.substring(0, href.indexOf("index.php?")) + "index.php?token=" + data;
  //             dp.set_notification_token_url(url);
  //           } else {

  //           }
  //         });

  //       }

  //       dp.addOnCloseCallback("notificator", _this.agent_notificator_initialization);
  //     }, function (error) {
  //       console.log("agent_notificator_initialization error: ");
  //       // setTimeout(_this.agent_notificator_initialization, 20000); откл
  //     });
  //   }
  // };

  this.query_notifications = function () {
    if (_this.tick <= _this.max_tick) {
      _this.tick++;
    }
    let delay = 0;
    let updateCount = function () {
      _this.update();
      count = _this.getCount();
      for (var i = 0; i < _this.update_callbacks.length; i++) {
        _this.update_callbacks[i](count);
      }
    };
    if (!_this.polling_period) {
      return;
    }
    if (_this.tick <= _this.max_tick) {
      delay = _this.polling_period * 1000;
    } else {
      delay = _this.polling_period * 6000;
    }


    // получаем дату последнего обновления, чтобы несколько вкладок не бомбили сервер
    let lastUpdate = 0;
    if (_this.tick > 1) {
      // НЕ первое открытие страницы; при первом нужно обновить данные с сервера, т.к. могли открыть док, находящийся в уведомлении
      lastUpdate = +localStorage.getItem('lastNotificationUpdate') || 0;
    }

    let now = new Date();
    if (lastUpdate + delay > now.getTime()) {
      delay = 0;
    }
    if (!delay) {
      setTimeout(_this.query_notifications, 1000);
      updateCount();
      return;
    }
    localStorage.setItem('lastNotificationUpdate', now.getTime());

    let url = 'index.php?route=common/notification/get_notification_count';
    let sound = new Howl({
      src: ['/misc/notify.mp3'],
      // autoplay: true,
      volume: 0.3
    });

    $.ajax({
      url: url,
      dataType: 'json'
    }).done(function (data) {
      let count = +_this.getCount();
      data = +data;
      if (data > count) {
        sound.play();
      }
      _this.setCount(data);
      updateCount();
      setTimeout(_this.query_notifications, delay);

    });

  };

  this.update = function () {
    let count = +_this.getCount();
    for (var i = 0; i < _this.update_callbacks.length; i++) {
      _this.update_callbacks[i](count);
    }

    let c = document.createElement("canvas"); // Используем тот же канвас
    c.height = c.width = 16;
    var ctx = c.getContext("2d");
    if (count > 0) {
      // ctx.globalAlpha = 0.7;
      ctx.drawImage(faviconT, 0, 0);

      ctx.globalAlpha = 1;
      ctx.font = "bold 10px Arial";
      ctx.fillStyle = "tomato";
      if (count < 10) {
        ctx.fillText(count, 5, 13, 16);
      } else if (count < 100) {
        ctx.fillText(count, 2, 13, 16);
      } else {
        ctx.fillText(count, 0, 13, 16);
      }
    } else {
      ctx.drawImage(favicon, 0, 0);

    }
    // применяем favicon
    let oldicons = document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
    for (var i = 0; i < oldicons.length; i++) {
      oldicons[i].parentNode.removeChild(oldicons[i]);
    }

    let newicon = document.createElement("link");
    newicon.setAttribute("rel", "icon");
    newicon.setAttribute("href", c.toDataURL());
    document.querySelector("head").appendChild(newicon);

  };
  setTimeout(this.query_notifications, 1500);

}

(function ($) {
  $.fn.notificator_widget = function (model) {
    var model = model;
    var _this = this;
    var dialog = $(this).find('#modal_notifications');
    var dropdown_list = $(this).find('div.dropdown-menu, div div div.modal-content');
    $(dropdown_list).css('overflow-y', 'auto');
    $(dropdown_list).css('max-height', '400px');
    $(dropdown_list).css('padding', '10px');
    var btn = $(this).find("span[type='button']");
    var count_element = $(this).find('span:last-child');
    $(btn).on('click', function (e) {
      let count = model.getCount();
      if (count == 0) {
        e.stopPropagation();
      }
    });

    $(this).on('show.bs.dropdown show.bs.modal', function (e) {
      let count = model.getCount();
      if (count) {
        var url = 'index.php?route=common/notification/get_notifications';
        $.ajax({
          url: url,
          dataType: 'json'
        }).done(function (data) {
          // notification_data = data;
          if (data) {
            let list_count = data.length;// < 10 ? data.length : 10;
            let html = "<table class='table table-hover' width='100%'><tbody>";
            for (var i = 0; i < list_count; i++) {
              html += "<tr id='notification_id_" + data[i]['notification_id'] + "_" + data[i]['document_uid'] + "'>";
              html += "<td><a style='display:block;' href='index.php?route=document/document&document_uid=" + data[i]['document_uid'] + "'><span>" + data[i]['message'] + "</span></a></td>";
              html += "<td style='width: 10px; text-align: right' class='pointer notification_remove'><span class='fa fa-close'></span></td><tr>";
            }
            html += "</tbody></table>";
            let table = $.parseHTML(html);
            $(table).find('tr td.notification_remove').on('click', remove_notification);
            dropdown_list.html("");
            dropdown_list.append(table);
            model.setCount(list_count);
            model.tick = 0;
            model.update();
          } else {
            dropdown_list.html("");
            model.setCount(0);
            model.update();
          }
        });
      }
    });

    model.registerUpdateCallback(function () {
      let count = model.getCount();
      count_element.text(count);
    });

    function remove_notification(e) {

      let notification_element = $(e.target).parents('tr');
      let ids = $(notification_element).attr("id").replace("notification_id_", "").split("_");
      let notification_id = ids[0];
      let document_uid = ids[1];
      model.tick = 0;
      var url = 'index.php?route=common/notification/remove_notification&document_uid=' + document_uid + '&notification_id=' + notification_id;
      let count = model.getCount();
      if (count) {
        model.setCount(--count);
      }
      if (count) {
        e.stopPropagation();
      }
      $.ajax({
        url: url,
        dataType: 'text'
      }).done(function (data) {
        notification_element.remove();
        model.update();
      });
    }

  };

})(jQuery);






