/* simple_table_widget JQuery плагин для табличного поля. Плагин работает только совместно с table.php
 * попытка реализации в парадигме MVC
 * */
(function ($) {
  function init(params) {
    console.log("method init: ");
    console.log(params);
  }

  $.fn.simple_table_widget = function (params) {
    var field_uid = this.selector.substring((this.selector.lastIndexOf('_') + 1));
    if (typeof params === 'object') {
      //var a = new TableValueContainer(this.find("input[type='hidden']"), this, params);
      var view = new TableView(this, params);
      var model = new TableModel(this.find("#" + params.element_id), params);
      //Для контроллера не будем создавать отдельный класс, контроллером будет является данный класс
      view.registerEditCallback(function (index) {
        if (index === -1)
          return;
        var row_data = model.getRow(index);
        view.loadModal(function (fields) {
          model.changeRow(index, fields);
        }, row_data, index);

      });
      view.registerAddCallback(function (index) {
        $('.tooltip').hide();
        view.loadModal(function (fields) {
          model.addNewRow(index, fields);
        });
      });

      view.registerDeleteCallback(function (indexes) {
        model.deleteRows(indexes);
      });

      view.registerCopyCallback(function (indexes) {
        model.copyRows(indexes);
      });
      view.registerPasteCallback(function (index) {
        model.insertRows(index);
      });

      model.registerChangeRowCallback(function (index, view_data) {
        view.changeRow(index, view_data);

      });
      model.registerAddNewRowCallback(function (index, view_data) {
        view.addRow(index, view_data);
      });
      model.registerDeleteRowCallback(function (index) {
        view.deleteRow(index);
      });


    } else if (typeof params === 'string') {
      console.log('method!: ');
      console.log(params);
    }

  };

  function TableView(target_element, params) {
    var field_uid = params.field_uid;
    var document_uid = params.document_uid;
    this.add_callbacks = [];
    this.edit_callbacks = [];
    this.copy_callbacks = [];
    this.delete_callbacks = [];
    this.paste_callbacks = [];
    this.buffer = [];
    //this.saverow_callbacks = [];
    this.field_uid = field_uid; //target_element.selector.substring((target_element.selector.lastIndexOf('_') + 1));

    var _this = this;
    // методы регистрации обработчиков событий представления
    this.registerAddCallback = function (callback) {
      _this.add_callbacks.push(callback);
    };
    this.registerDeleteCallback = function (callback) {
      _this.delete_callbacks.push(callback);
    };
    this.registerEditCallback = function (callback) {
      _this.edit_callbacks.push(callback);
    };
    this.registerCopyCallback = function (callback) {
      _this.copy_callbacks.push(callback);
    };
    this.registerPasteCallback = function (callback) {
      _this.paste_callbacks.push(callback);
    };
    /*this.registerSaveRowCallback = function (callback) {
     _this.saverow_callbacks.push(callback);
     }*/

    var main_checkbox = target_element.find("thead#table_head_" + this.field_uid + ">tr:first-child >td:first-child :checkbox");

    var tbody = target_element.find("tbody#table_body_" + this.field_uid);

    var button_paste = target_element.find("button[name='btn_paste_rows']");
    //кнопки которые нужно скрывать, если нет выделения строк чекбоксами
    var button_copy = target_element.find("button[name='btn_copy_rows']");
    var button_delete = target_element.find("button[name='btn_delete_rows']");
    /* Выделение/снятие всех чекбоксов. Для этого события не нужна регистрация обработчиков, т.к. 
     * выделение чекбоксов относится исключительно к представлению и ни как не затрагивает модель.
     */

    main_checkbox.on("change.simple_table_widget", function (e) {
      var checkboxes = tbody.find(">tr >td:first-child >:checkbox");
      if (e.target.checked) {
        checkboxes.prop("checked", true);
        button_copy.prop('disabled', false);
        button_delete.prop('disabled', false);
      } else {
        checkboxes.prop("checked", false);
        button_copy.prop('disabled', true);
        button_delete.prop('disabled', true);
      }
    });

    /* Базовый обработчик клика на тело таблицы, вызвает зарегистрированные обработчики.
     * Будет использоваться для редактирования строки
     */
    tbody.on('click.simple_table_widget', function (e) {
      var tagName = e.target.tagName.toUpperCase();
      if (tagName === "A") {
        e.preventDefault(); //если в таблице отображалась гиперссылка, отменяем переход по ней
      } else if (tagName === "TD" && $(e.target).children('input[type=\'checkbox\']').length) {
        $(e.target).children('input[type=\'checkbox\']').trigger('click'); //клик по ячейке с чекбоксом - строку не открываем, "кликаем" по чек-боксу
        return;
      }

      var index = getCurrentIndex(tbody.find(">tr"), e.target);
      //скрываем или показываем кнопки "копировать" и "удалить", в зависимости от выделения строк чекбоксами
      if (index === -1) {
        var _tbody_rows = tbody.find(">tr");
        var indexes = getSelectedIndexes(_tbody_rows);
        if (indexes.length > 0) {
          button_copy.prop('disabled', false);
          button_delete.prop('disabled', false);
        } else {
          button_copy.prop('disabled', true);
          button_delete.prop('disabled', true);
        }
        //скрываем или показываем главный чекбокс, при выделении чекбоксами строк
        if (_tbody_rows.length === indexes.length) {
          main_checkbox.prop('checked', true);
        } else {
          main_checkbox.prop('checked', false);
        }
      } else {
        for (var i = 0; i < _this.edit_callbacks.length; i++) {
          _this.edit_callbacks[i](index);
        }
      }
    });

    /* Базовый обработчик кнопки AddRow, вызвает зарегистрированные обработчики.
     * Будет использоваться для добавления новой строки
     */
    target_element.find("button[type='button'][name='btn_add_row']").
      on('click.simple_table_widget', function (e) {
        var _tbody_rows = tbody.find(">tr");
        var indexes = getSelectedIndexes(_tbody_rows);
        var index = -1;
        if (indexes) {
          index = indexes[0];
        }
        for (var i = 0; i < _this.add_callbacks.length; i++) {
          _this.add_callbacks[i](index);
        }
      });

    /* Базовый обработчик кнопки DeleteRow, вызвает зарегистрированные обработчики.
     * Будет использоваться для добавления новой строки
     */

    button_delete.on('click.simple_table_widget', function (e) {
      var _tbody_rows = tbody.find(">tr");
      var indexes = getSelectedIndexes(_tbody_rows);
      for (var i = 0; i < _this.delete_callbacks.length; i++) {
        _this.delete_callbacks[i](indexes);
      }
    });

    button_copy.on('click.simple_table_widget', function (e) {
      var _tbody_rows = tbody.find(">tr");
      var indexes = getSelectedIndexes(_tbody_rows);
      if (indexes.length > 0) {
        _this.buffer.length = 0;
        button_paste.prop('disabled', false);
        for (var i = 0; i < indexes.length; i++) {
          var index = indexes[i];
          var tr = tbody.find(">tr:nth-child(" + (index + 1) + ")").clone();
          tr.find(">td:first-child >:checkbox").prop("checked", false);

          _this.buffer.push(tr);
        }
        for (var i = 0; i < _this.copy_callbacks.length; i++) {
          _this.copy_callbacks[i](indexes);
        }
      }
    });

    button_paste.on('click.simple_table_widget', function (e) {
      var _tbody_rows = tbody.find(">tr");
      var indexes = getSelectedIndexes(_tbody_rows);
      if (_this.buffer.length > 0) {
        main_checkbox.prop("checked", false);
      }
      if (indexes.length > 0) {
        var index = indexes[0];
        var tr = tbody.find(">tr:nth-child(" + (index + 1) + ")");
        for (var i = 0; i < _this.buffer.length; i++) {
          tr.before(_this.buffer[i]);
          _this.buffer[i] = _this.buffer[i].clone();
        }

      } else {

        for (var i = 0; i < _this.buffer.length; i++) {
          tbody.append(_this.buffer[i]);
          _this.buffer[i] = _this.buffer[i].clone();
        }
      }
      for (var i = 0; i < _this.paste_callbacks.length; i++) {
        _this.paste_callbacks[i](indexes[0]);
      }

    });

    //Загрузка формы редактирования строки
    this.loadModal = function (callback, row_data, index) {
      $('#modal-itable').remove();
      var html = '<div id="modal-itable" class="modal fade">';
      html += '  <div class="modal-dialog modal-lg">';
      html += '    <div class="modal-content">';
      //html += 'test modal';
      html += '    </div>';
      html += '  </div>';
      html += '</div>';
      $('body').append(html);
      $('#modal-itable').modal('show');
      $('#modal-itable').draggable({ handle: '.modal-header' });
      var _this = this;
      var ajax_settings = {
        success: function (data) {
          $('#modal-itable .modal-load-mask').remove();
          $('#modal-itable .modal-content').prepend(data);
          //Подключаем обработчик для кнопки сохранения
          $('#modal-itable #modal-field-add_inner, #modal-itable #modal-button-add_inner_row').
            on("click", function () {
              //формируем данные и передаем их зарегистрированному обработчику
              var fields = $('#modal-itable [name^="field["][type!=\'checkbox\'][type!=\'radio\'], #modal-itable input[name^="field["][type=\'checkbox\']:checked, #modal-itable input[name^="field["][type=\'radio\']:checked');
              callback(fields);

              //$('#modal-itable')
            });
          //$('#modal-itable #modal-button-add_inner').on("click", save_callback);
        },
        error: function (xhr, ajaxOptions, thrownError) {
          console.log(thrownError);
          console.log('editField ajax error');
          $('#modal-itable .modal-load-mask .fa').remove();
          $('#modal-itable .modal-content').prepend(xhr.responseText);
        }
      };

      if (!row_data) {
        //Добавление новой строки в таблицу
        ajax_settings.url = 'index.php?route=field/table/getTableRowForm&field_uid=' + _this.field_uid + '&document_uid=' + document_uid;
        ajax_settings.type = 'get';
        ajax_settings.dataType = 'html';
      } else {
        //Редактирование действия
        ajax_settings.url = 'index.php?route=field/table/getTableRowForm&field_uid=' + _this.field_uid + '&document_uid=' + document_uid;
        ajax_settings.data = { 'row_data': JSON.stringify(row_data) };
        ajax_settings.type = 'post';
        ajax_settings.dataType = 'html';
      }
      $.ajax(ajax_settings);
    };

    this.addRow = function (index, view_data) {
      var tr;
      if (index >= 0) {
        tr = tbody.find(">tr:nth-child(" + (index + 1) + ")");
        tr.before(view_data);
      } else {
        tbody.append(view_data);
      }

    };

    this.changeRow = function (index, view_data) {
      var tr;
      if (index >= 0) {
        tr = tbody.find(">tr:nth-child(" + (index + 1) + ")");
        tr.replaceWith(view_data);
      }
    };

    this.deleteRow = function (index) {
      var tr;
      if (index >= 0) {
        tr = tbody.find(">tr:nth-child(" + (index + 1) + ")");
        tr.remove();
      }
    };

    function getCurrentIndex(tbody_rows, target) {
      //возвращает индекс строки, на которой произошло событие clicks
      var cnt = 0;
      if (target.tagName === 'INPUT' && target.type === 'checkbox')
        return -1;
      while (!tbody_rows.is(target) && cnt < 20) {
        target = target.parentNode;
        cnt++;
      }
      if (cnt > 20)
        return -1;
      return tbody_rows.index(target);
    }

    function getSelectedIndexes(tbody_rows) {
      //возвращает индексы выбранных строк
      var checkboxes = tbody_rows.find(">td:first-child >:checkbox");
      var selected = tbody_rows.find(">td:first-child >:checkbox:checked");
      var indexes = [];
      var index = -1;
      for (var i = 0; i < selected.length; i++) {
        index = checkboxes.index(selected[i]);
        if (index !== -1) {
          indexes.push(index);
        }
      }
      return indexes;
    }
  }

  //Конструктор модели таблицы
  function TableModel(data_field, params) {
    this.addnew_row_callbacks = [];
    this.change_row_callbacks = [];
    this.delete_row_callbacks = [];
    this.insert_row_callbacks = [];
    var field_name = data_field[0].name;
    this.field_uid = params.field_uid;//field_name.substring((field_name.lastIndexOf('_') + 1));
    this.data_field = data_field;
    this.buffer = [];
    var _this = this;
    // методы регистрации обработчиков событий модели
    this.registerAddNewRowCallback = function (callback) {
      _this.addnew_row_callbacks.push(callback);
    };
    this.registerChangeRowCallback = function (callback) {
      _this.change_row_callbacks.push(callback);
    };
    this.registerDeleteRowCallback = function (callback) {
      _this.delete_row_callbacks.push(callback);
    };
    this.registerInsertRowCallback = function (callback) {
      _this.insert_row_callbacks.push(callback);
    };
    //инициализация
    //this.doctype_uid = params.doctype_uid;
    this.table_data = [];
    //this.lang_id = params.lang_id;
    var values = data_field.val();
    if (values) {
      try {
        this.table_data = JSON.parse(values);
      } catch (err) {
        console.log(err);
        this.data_field.val('');
      }
    }

    this.getRow = function (index) {
      return _this.table_data[index];
    };
    this.addNewRow = function (index, fields) {
      if (typeof field_functions !== "undefined" && field_functions.length) {
        var result = "";
        var stop = false;
        $.each(field_functions, function () {
          result = this();
          if (result) {
            alert(result);
            stop = true;
            return false;
          }
        });
        if (stop) {
          return false;
        }
      }
      // отправка данных из формы редактирования строки таблицы на сервер
      $.ajax({
        url: 'index.php?route=field/table/getTableRow&field_uid=' + _this.field_uid + '&document_uid=' + params.document_uid,
        data: fields,
        type: 'post',
        dataType: 'json',
        success: function (data) {
          if (data['error']) {
            alert(data['error']);
            return;
          }
          if (index >= 0) {
            _this.table_data.splice(index, 0, data['row_values']);
          } else {
            _this.table_data.push(data['row_values']);
          }
          for (var i = 0; i < _this.addnew_row_callbacks.length; i++) {
            _this.addnew_row_callbacks[i](index, data['row_views']);
          }
          _this.data_field.val(JSON.stringify(_this.table_data));
          _this.data_field.trigger('change');
          $('#modal-itable').modal('hide');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          console.log(thrownError);
          console.log('editField ajax error');
        }
      });

    };
    this.changeRow = function (index, fields) {
      if (typeof field_functions !== "undefined" && field_functions.length) {
        var result = "";
        var stop = false;
        $.each(field_functions, function () {
          result = this();
          if (result) {
            alert(result);
            stop = true;
            return false;
          }
        });
        if (stop) {
          return false;
        }
      }
      // отправка данных из формы редактирования строки таблицы на сервер
      $.ajax({
        url: 'index.php?route=field/table/getTableRow&field_uid=' + _this.field_uid + '&document_uid=' + params.document_uid,
        data: fields,
        type: 'post',
        dataType: 'html',
        success: function (json) {
          var data = JSON.parse(json);
          if (data['error']) {
            alert(data['error']);
            return;
          }
          //изменяем элемент массива
          _this.table_data[index] = data['row_values'];
          for (var i = 0; i < _this.change_row_callbacks.length; i++) {
            _this.change_row_callbacks[i](index, data['row_views']);
          }
          _this.data_field.val(JSON.stringify(_this.table_data));
          _this.data_field.trigger('change');
          $('#modal-itable').modal('hide');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          console.log(thrownError);
          console.log('editField ajax error');
        }
      });
    };

    this.copyRows = function (indexes) {
      if (indexes.length === 0)
        return;
      this.buffer.length = 0;
      for (var i = 0; i < indexes.length; i++) {
        var index = indexes[i];
        this.buffer.push(_this.table_data[index]);
      }
    };

    this.insertRows = function (index) {
      if (_this.buffer.length > 0) {
        for (var i = _this.buffer.length - 1; i >= 0; i--) {
          _this.table_data.splice(index, 0, _this.buffer[i]);
        }
      }

      _this.data_field.val(JSON.stringify(_this.table_data));
      _this.data_field.trigger('change');
    };

    this.deleteRows = function (indexes) {

      for (var i = 0; i < indexes.length; i++) {
        var index = indexes[i] - i;
        _this.table_data.splice(index, 1);
        for (var j = 0; j < _this.delete_row_callbacks.length; j++) {
          _this.delete_row_callbacks[j](index);
        }
      }
      _this.data_field.val(_this.table_data.length ? JSON.stringify(_this.table_data) : '');
      _this.data_field.trigger('change');
    };

  }

})(jQuery);





