/* 
* To change this license header, choose License Headers in Project Properties.
* To change this template file, choose Tools | Templates
* and open the template in the editor.
*/

function FieldContainer(field_name, target_field, target_table, descriptions_field, doctype_uid, prefix, lang_id) {
  this.count = 0;
  this.target_field = $(target_field);
  this.target_table = $(target_table);
  this.description_field = $(descriptions_field);
  this.doctype_uid = doctype_uid;
  this.prefix = prefix;
  this.field_name = field_name;
  this.fields = [];
  this.descriptions = [];
  this.lang_id = lang_id;

  this.max_field_uid = 0; // макс ИД поля; было поля 1,2,3, удалили 3, добавили новое - оно должно быть 4


  this.deleteField = function (index) {
    var _this = this;
    _this.fields.splice(index, 1);
    _this.descriptions.splice(index, 1);
    if (_this.descriptions.length !== 0) {
      _this.target_field.val(JSON.stringify(_this.fields));
      _this.description_field.val(JSON.stringify(_this.descriptions));
    } else {
      _this.target_field.val('');
      _this.description_field.val('');
    }

    _this.refreshview();
  };

  this.moveFieldUp = function (index) {
    var _this = this;
    if (index > 0) {
      var field = _this.fields[index];
      _this.fields[index] = _this.fields[index - 1];
      _this.fields[index - 1] = field;
      var description = _this.descriptions[index];
      _this.descriptions[index] = _this.descriptions[index - 1];
      _this.descriptions[index - 1] = description;
      _this.target_field.val(JSON.stringify(_this.fields));
      _this.description_field.val(JSON.stringify(_this.descriptions));
      _this.refreshview();
    }
  };

  this.moveFieldDown = function (index) {
    var _this = this;
    if (index < _this.fields.length - 1) {
      var field = _this.fields[index];
      _this.fields[index] = _this.fields[index + 1];
      _this.fields[index + 1] = field;
      var description = _this.descriptions[index];
      _this.descriptions[index] = _this.descriptions[index + 1];
      _this.descriptions[index + 1] = description;
      _this.target_field.val(JSON.stringify(_this.fields));
      _this.description_field.val(JSON.stringify(_this.descriptions));
      _this.refreshview();
    }
  };

  this.editField = function (index) {
    $('#modal-ifield').remove();

    html = '<div id="modal-ifield" class="modal fade">';
    html += '  <div class="modal-dialog modal-lg">';
    html += '    <div class="modal-content">';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';
    $('body').append(html);
    $('#modal-ifield').modal('show');
    $('#modal-ifield').draggable({ handle: '.modal-header' });
    $('#modal-ifield').on('hidden.bs.modal', function () {
      //удаляем при закрытии, чтобы не сохранилась форма с полем, которая может перекрыть фонрму добавляемого поля вне таблицы
      $('#modal-ifield').remove();
    });

    var _this = this;
    var inner_field_uid = undefined;
    if (index === undefined) {
      inner_field_uid = ++_this.max_field_uid;
    } else {
      if (_this.fields[index].inner_field_uid) {
        inner_field_uid = _this.fields[index].inner_field_uid;
        if (inner_field_uid > _this.max_field_uid) {
          _this.max_field_uid = inner_field_uid;
        }
      }
      else {
        inner_field_uid = ++_this.max_field_uid;
      }
    }

    var ajax_settings = {
      success: function (data) {
        $('#modal-ifield .modal-load-mask').remove();
        $('#modal-ifield .modal-content').prepend(data);
        //костыль для отключения настройки актуализации ссылочного поля
        $('select[name="field[disabled_actualize]"]').val('0');
        $('select[name="field[disabled_actualize]"]').attr('disabled', 'disabled');
        //Подключаем обработчик для кнопки сохранения
        $('#modal-field-add_inner, #modal-button-add_inner').on("click", getOnSaveHandler(index, _this, inner_field_uid));
      },
      error: function (xhr, ajaxOptions, thrownError) {
        $('#modal-ifield .modal-load-mask .fa').remove();
        $('#modal-ifield .modal-content').prepend(xhr.responseText);
      }
    };

    if (index === undefined) {
      //Добавление нового поля

      ajax_settings.url = 'index.php?route=extension/field/' + _this.field_name + '/getInnerFieldForm&doctype_uid=' + this.doctype_uid;
      ajax_settings.type = 'get';
      ajax_settings.dataType = 'html';
    } else {
      //Редактирование поля
      ajax_settings.url = 'index.php?route=extension/field/' + _this.field_name + '/getInnerFieldForm&doctype_uid=' + this.doctype_uid + '&index=' + index;
      ajax_settings.data = this.fields[index];
      ajax_settings.type = 'post';
      ajax_settings.dataType = 'html';
    }
    $.ajax(ajax_settings);

    function getOnSaveHandler(index, _this, inner_field_uid) {
      return function () {
        _this.save(index, inner_field_uid);
      };
    }


  };

  this.save = function (index, inner_field_uid) {
    var _this = this;
    var col_titles = $('#modal-ifield [name^="field[column_title]["]');
    var data = $('#form_inner_field [name^="field["][type!=\'checkbox\'][type!=\'radio\'], #form_inner_field input[name^="field["][type=\'checkbox\']:checked, #form_inner_field input[name^="field["][type=\'radio\']:checked');
    //если поле не выбрано, ничего не сохранять
    if ($('#modal-ifield select[name="inner_field_type"]').val() === '0') return;
    var json = "";
    var field = {};
    field.field_type = $('#input-inner_field_type').val();
    field.field_form_display = $('#input-inner_field_form_display').val();
    field.field_form_required = $('#input-inner_field_form_required').val();
    field.field_view_display = $('#input-inner_field_view_display').val();
    field.inner_field_uid = inner_field_uid;
    field.params = {};
    for (var i = 0; i < data.length; i++) {
      getFieldParamObj(data[i].name, field.params, data[i].value);
    }
    for (var i = 0; i < col_titles.length; i++) {
      getFieldParamObj(col_titles[i].name, field, col_titles[i].value);
    }

    var ajax_settings = {
      url: 'index.php?route=extension/field/' + _this.field_name + '/getInnerFieldDescription',
      data: field,
      type: 'post',
      dataType: 'json',
      success: function (data) {
        if (index === undefined) {
          //добавление нового действия
          _this.fields.push(field);
          _this.descriptions.push(data);
        } else {
          //изменение старого действия
          _this.fields[index] = field;
          _this.descriptions[index] = data;
        }
        _this.target_field.val(JSON.stringify(_this.fields));
        _this.description_field.val(JSON.stringify(_this.descriptions));
        _this.refreshview();
      },
      error: function (xhr, ajaxOptions, thrownError) {
        //$('#modal-ifield .modal-load-mask .fa').remove();
        //$('#modal-ifield .modal-content').prepend(xhr.responseText);
      }
    };
    $.ajax(ajax_settings);
    _this.refreshview();

  };

  this.initfc = function () {
    var fields = this.target_field.val();
    var descriptions = this.description_field.val();
    if (fields !== "") {
      try {
        this.fields = JSON.parse(fields);
      }
      catch (err) {
        this.target_field.val('');
      }
    }
    if (descriptions !== "") {
      try {
        this.descriptions = JSON.parse(descriptions);
      }
      catch (err) {
        this.description_field.val('');
      }
    }
    for (var i = 0; i < this.fields.length; i++) {
      var last_inner_field_uid = parseInt(this.fields[i].inner_field_uid);
      if (!isNaN(last_inner_field_uid) && last_inner_field_uid > this.max_field_uid) {
        this.max_field_uid = last_inner_field_uid;
      }
    }
    this.refreshview();
  };

  this.refreshview = function () {
    this.target_table.html('');
    var html = '<table class="table table-hover">';
    for (var i = 0; i < this.fields.length; i++) {
      //html += '<div class="form-group">';
      html += '<tr>';
      if (i < this.descriptions.length) {
        html += '<td><span class="btn btn-default" id="inner_field_edit_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
        html += this.descriptions[i]['name'];
        html += '</span></td>';
        html += '<td><div class="text-info">' + this.fields[i]['column_title'][this.lang_id] + '</div></td>';
      } else {
        html += '<td></td><td></td>';
      }

      html += '<td style="width: 96px"><span class="btn btn-default" id="inner_field_up_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
      html += '<i class="fa fa-arrow-up"></i>';
      html += '</span>';
      html += '<span class="btn btn-default" id="inner_field_down_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
      html += '<i class="fa fa-arrow-down"></i>';
      html += '</span></td>';

      html += '<td style="width: 48px"><span class="btn btn-default" id="inner_field_delete_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
      html += '<i class="fa fa-minus-circle"></i>';
      html += '</span></td>';
      //html += '</div>';
      html += '</tr>';
    }
    html += '</table>';
    this.target_table.append(html);
    for (var i = 0; i < this.fields.length; i++) {
      $('#inner_field_edit_' + this.prefix + '_' + i).on("click", getOnEditHandler(i, this));
      $('#inner_field_up_' + this.prefix + '_' + i).on("click", getOnMoveUpHandler(i, this));
      $('#inner_field_down_' + this.prefix + '_' + i).on("click", getOnMoveDownHandler(i, this));
      $('#inner_field_delete_' + this.prefix + '_' + i).on("click", getOnDeleteHandler(i, this));
    }
  };

  function getOnEditHandler(index, _this) {
    return function () {
      _this.editField(index);
    };
  }

  function getOnMoveUpHandler(index, _this) {
    return function () {
      _this.moveFieldUp(index);
    };
  }

  function getOnMoveDownHandler(index, _this) {
    return function () {
      _this.moveFieldDown(index);
    };
  }

  function getOnDeleteHandler(index, _this) {
    return function () {
      _this.deleteField(index);
    };
  }

  //рекурсивная обработка вложенных объектов
  function getFieldParamObj(name, param_obj, value) {
    var i1 = name.indexOf('[') + 1;
    var i2 = name.indexOf(']');
    var param_name = name.substring(i1, i2);
    var rest = name.substring(i2 + 1);
    if (rest.length === 0) {
      param_obj[param_name] = value;
      return param_obj;
    }
    if (!param_obj[param_name]) {
      param_obj[param_name] = {};
    }
    param_obj[param_name] = getFieldParamObj(rest, param_obj[param_name], value);
    return param_obj;
  }
}
