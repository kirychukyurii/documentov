/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//console.log("actioncontainer loading...");

function ActionContainer(action_name, target_field, target_table, descriptions_field, doctype_uid, prefix, context, routeUid) {
  this.count = 0;
  this.target_field = $(target_field);
  this.target_table = $(target_table);
  this.description_field = $(descriptions_field);
  this.doctype_uid = doctype_uid;
  this.prefix = prefix;
  this.action_name = action_name;
  this.context = context;
  this.routeUid = routeUid;
  this.actions = [];
  this.descriptions = [];

  this.deleteAction = function (index) {
    var _this = this;
    //_this.actions.splice(index, 1);
    //_this.descriptions.splice(index, 1);
    _this.actions[index]['deleted'] = 1;
    if (_this.descriptions.length !== 0) {
      _this.target_field.val(JSON.stringify(_this.actions));
      _this.description_field.val(JSON.stringify(_this.descriptions));
    } else {
      _this.target_field.val('');
      _this.description_field.val('');
    }

    _this.refreshview();
  };

  this.undeleteAction = function (index) {
    var _this = this;
    delete _this.actions[index]['deleted'];
    if (_this.descriptions.length !== 0) {
      _this.target_field.val(JSON.stringify(_this.actions));
      _this.description_field.val(JSON.stringify(_this.descriptions));
    } else {
      _this.target_field.val('');
      _this.description_field.val('');
    }

    _this.refreshview();
  };

  this.moveActionUp = function (index) {
    var _this = this;
    if (index > 0) {
      _this.actions[index].new = 1;
      _this.actions[index - 1].new = 1;
      var action = _this.actions[index];
      _this.actions[index] = _this.actions[index - 1];
      _this.actions[index - 1] = action;
      var description = _this.descriptions[index];
      _this.descriptions[index] = _this.descriptions[index - 1];
      _this.descriptions[index - 1] = description;
      _this.target_field.val(JSON.stringify(_this.actions));
      _this.description_field.val(JSON.stringify(_this.descriptions));
      _this.refreshview();
    }
  };

  this.moveActionDown = function (index) {
    var _this = this;
    if (index < _this.actions.length - 1) {
      _this.actions[index].new = 1;
      _this.actions[index + 1].new = 1;
      ;
      var action = _this.actions[index];
      _this.actions[index] = _this.actions[index + 1];
      _this.actions[index + 1] = action;
      var description = _this.descriptions[index];
      _this.descriptions[index] = _this.descriptions[index + 1];
      _this.descriptions[index + 1] = description;
      _this.target_field.val(JSON.stringify(_this.actions));
      _this.description_field.val(JSON.stringify(_this.descriptions));
      _this.refreshview();
    }
  };

  this.editAction = function (index) {
    $('#modal-iaction').remove();

    html = '<div id="modal-iaction" class="modal fade">';
    html += '  <div class="modal-dialog modal-lg">';
    html += '    <div class="modal-content">';
    //html += 'test modal';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';
    $('body').append(html);
    $('#modal-iaction').modal('show');
    $('#modal-iaction').draggable({ handle: '.modal-header' });
    $('#modal-iaction').on('hidden.bs.modal', function () {
      $('#modal-iaction').remove();
    });
    var _this = this;

    var ajax_settings = {
      success: function (data) {
        $('#modal-iaction .modal-load-mask').remove();
        $('#modal-iaction .modal-content').prepend(data);
        //Подключаем обработчик для кнопки сохранения
        $('#condition-modal-action-add_inner').on("click", getOnSaveHandler(index, _this));
        $('#condition-modal-button-add_inner').on("click", getOnSaveHandler(index, _this));


      },
      error: function (xhr, ajaxOptions, thrownError) {
        console.log('editAction ajax error');
        $('#modal-iaction .modal-load-mask .fa').remove();
        $('#modal-iaction .modal-content').prepend(xhr.responseText);
      }
    };

    if (index === undefined) {
      //Добавление нового действия 
      ajax_settings.url = 'index.php?route=extension/action/' + _this.action_name + '/getInnerActionForm&doctype_uid=' + this.doctype_uid + '&context=' + this.context + '&route_uid=' + this.routeUid;
      ajax_settings.type = 'get';
      ajax_settings.dataType = 'html';
    } else {
      //Редактирование действия
      ajax_settings.url = 'index.php?route=extension/action/' + _this.action_name + '/getInnerActionForm&doctype_uid=' + this.doctype_uid + '&index=' + index + '&context=' + this.context + '&route_uid=' + this.routeUid;
      ajax_settings.data = this.actions[index];
      ajax_settings.type = 'post';
      ajax_settings.dataType = 'html';
    }
    $.ajax(ajax_settings);

    function getOnSaveHandler(index, _this) {
      return function () {
        _this.save(index);
      };
    }
  };

  this.save = function (index) {
    var _this = this;
    var action_name = $('#select-condition-inner_action').val();
    var data = $('#condition-form_inner_action input[type!=\'checkbox\'][type!=\'radio\'][name^="action["],  #condition-form_inner_action input[name^="action["][type=\'checkbox\']:checked,  #condition-form_inner_action input[name^="action["][type=\'radio\']:checked, #condition-form_inner_action textarea[name^="action["], #condition-form_inner_action select[name^="action["]');
    var json = "";
    var action = {};
    action.action = action_name;
    action.params = {};
    action.new = 1;
    for (var i = 0; i < data.length; i++) {
      //action.params[data[i].name.substring(data[i].name.indexOf('[') + 1, data[i].name.lastIndexOf(']'))] = data[i].value;
      getActionParamObj(data[i].name, action.params, data[i].value);
    }

    var ajax_settings = {
      url: 'index.php?route=extension/action/' + _this.action_name + '/getInnerActionDescription',
      data: action,
      type: 'post',
      dataType: 'json',
      success: function (data) {
        if (index === undefined) {
          //добавление нового действия
          _this.actions.push(action);
          _this.descriptions.push(data);
        } else {
          //изменение старого действия
          _this.actions[index] = action;
          _this.descriptions[index] = data;
        }
        _this.target_field.val(JSON.stringify(_this.actions));
        _this.description_field.val(JSON.stringify(_this.descriptions));
        _this.refreshview();
      },
      error: function (xhr, ajaxOptions, thrownError) {
        console.log('save ajax error');
        //$('#modal-iaction .modal-load-mask .fa').remove();
        //$('#modal-iaction .modal-content').prepend(xhr.responseText);
      }
    };
    $.ajax(ajax_settings);
  };
  this.initac = function () {
    var actions = this.target_field.val();
    var descriptions = this.description_field.val();
    if (actions !== "") {
      this.actions = JSON.parse(actions);
    }
    if (descriptions !== "") {
      this.descriptions = JSON.parse(descriptions);
    }
    this.refreshview();
  };

  this.refreshview = function () {
    $('#modal-iaction .modal-content').remove();
    this.target_table.html('');
    var html = '<table class="table table-hover">';
    var style = "";
    for (var i = 0; i < this.actions.length; i++) {
      //html += '<div class="form-group">';

      if (i < this.descriptions.length) {
        if (this.actions[i]['deleted']) {
          html += '<tr class="remove_element">';
          html += '<td><button type="button" class="btn btn-default remove_element" id="condition-inner_action_edit_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" disabled>';
          html += this.descriptions[i]['name'];
          html += '</button></td>';
          html += '<td><div class="text-info remove_element">' + this.descriptions[i]['description'] + '</div></td>';
        } else if (this.actions[i]['new']) {
          html += '<tr class="new_element">';
          html += '<td><button type="button" class="btn btn-default new_element" id="condition-inner_action_edit_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="">';
          html += this.descriptions[i]['name'];
          html += '</button></td>';
          html += '<td><div class="text-info new_element">' + this.descriptions[i]['description'] + '</div></td>';

        } else {
          html += '<tr>';
          html += '<td><button type="button" class="btn btn-default" id="condition-inner_action_edit_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="">';
          html += this.descriptions[i]['name'];
          html += '</button></td>';
          html += '<td><div class="text-info">' + this.descriptions[i]['description'] + '</div></td>';

        }




      } else {
        html += '<td></td><td></td>';
      }

      html += '<td style="width: 100px"><span class="btn btn-default" id="condition-inner_action_up_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
      html += '<i class="fa fa-arrow-up"></i>';
      html += '</span>';
      html += '<span class="btn btn-default" id="condition-inner_action_down_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
      html += '<i class="fa fa-arrow-down"></i>';
      html += '</span></td>';
      if (this.actions[i]['deleted']) {
        html += '<td><span class="btn btn-default" id="condition-inner_action_undelete_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
        html += '<i class="fa fa-undo"></i>';
      } else {
        html += '<td><span class="btn btn-default" id="condition-inner_action_delete_' + this.prefix + '_' + i + '" data-toggle="tooltip" title="" >';
        html += '<i class="fa fa-minus-circle"></i>';
      }

      html += '</span></td>';
      //html += '</div>';
      html += '</tr>';
    }
    html += '</table>';
    this.target_table.append(html);
    for (var i = 0; i < this.actions.length; i++) {
      $('#condition-inner_action_edit_' + this.prefix + '_' + i).on("click", getOnEditHandler(i, this));
      $('#condition-inner_action_up_' + this.prefix + '_' + i).on("click", getOnMoveUpHandler(i, this));
      $('#condition-inner_action_down_' + this.prefix + '_' + i).on("click", getOnMoveDownHandler(i, this));
      if (this.actions[i]['deleted']) {
        $('#condition-inner_action_undelete_' + this.prefix + '_' + i).on("click", getOnUndeleteHandler(i, this));
      } else {
        $('#condition-inner_action_delete_' + this.prefix + '_' + i).on("click", getOnDeleteHandler(i, this));
      }
    }
  };

  function getOnEditHandler(index, _this) {
    return function () {
      _this.editAction(index);
    };
  }

  function getOnMoveUpHandler(index, _this) {
    return function () {
      _this.moveActionUp(index);
    };
  }

  function getOnMoveDownHandler(index, _this) {
    return function () {
      _this.moveActionDown(index);
    };
  }

  function getOnDeleteHandler(index, _this) {
    return function () {
      _this.deleteAction(index);
    };
  }
  function getOnUndeleteHandler(index, _this) {
    return function () {
      _this.undeleteAction(index);
    };
  }
  //рекурсивная обработка вложенных объектов
  function getActionParamObj(name, param_obj, value) {
    var i1 = name.indexOf('[') + 1;
    var i2 = name.indexOf(']');
    var param_name = name.substring(i1, i2);
    if (param_name === '') {
      //getprint(name, param_obj, value)();

      //return param_obj;
    }
    var rest = name.substring(i2 + 1);
    if (rest === '[]') {
      if (!param_obj[param_name]) {
        param_obj[param_name] = [value];
      }
      else {
        param_obj[param_name].push(value);
      }
      return param_obj;
    }
    if (rest.length === 0) {
      param_obj[param_name] = value;
      return param_obj;
    }
    if (!param_obj[param_name]) {
      param_obj[param_name] = {};
    }
    param_obj[param_name] = getActionParamObj(rest, param_obj[param_name], value);
    return param_obj;
  }

  function getprint(nm, pr, vl) {
    return function () {
      console.log("NM: " + nm);
      console.log("PR: ");
      console.log(pr);
      console.log("VL: ");
      console.log(vl);
    };
  }
}
