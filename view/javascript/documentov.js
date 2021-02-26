class Documentov {

  constructor(data) {
    let text = JSON.parse(data);
    Documentov.languageCode = text.code;
    Documentov.text = text;

    $.ajax({
      url: 'index.php?route=common/setting/getLanguages',
      dataType: 'json',
      success: function (json) {
        Documentov.lang = json;
      }
    });

  }

  static showModalWindow(idWindow, titleWindow, bodyWindow, idButtonSave, sizeWindow = 'lg', isButtonCancel = true, titleSave, titleClose, options = { show: true }) {
    let $div_modal = $('<div>').attr('id', idWindow).attr('role', 'dialog').addClass('modal fade').css('z-index', '10000');
    let $div_modal_dialog = $('<div>').addClass('modal-dialog modal-' + sizeWindow);
    let $div_modal_dialog_content = $('<div>').addClass('modal-content');
    let $div_modal_dialog_content_header = $('<div>').addClass('modal-header');
    $div_modal_dialog_content_header.append($('<button>').attr('type', 'button').attr('data-dismiss', 'modal').addClass('close').html('&times;'));
    $div_modal_dialog_content_header.append($('<h4>').addClass('modal-title').html(titleWindow));
    let $div_modal_dialog_content_body = $('<div>').addClass('modal-body').append(bodyWindow);
    let $div_modal_dialog_content_footer = $('<div>').addClass('modal-footer');
    $div_modal_dialog_content_footer.append($('<button>').attr('type', 'button').attr('id', idButtonSave).attr('data-dismiss', 'modal').addClass('btn btn-default').html(titleSave || Documentov.text.button_save));
    if (isButtonCancel) {
      $div_modal_dialog_content_footer.append($('<button>').attr('type', 'button').attr('data-dismiss', 'modal').addClass('btn btn-default').html(titleClose || Documentov.text.button_close));
    }
    $div_modal_dialog_content.append($div_modal_dialog_content_header).append($div_modal_dialog_content_body).append($div_modal_dialog_content_footer);
    $div_modal_dialog.append($div_modal_dialog_content);
    $div_modal.append($div_modal_dialog);
    $div_modal.appendTo('body');
    $div_modal.modal(options);
    $div_modal.draggable({ handle: '.modal-header' });
    $('#' + idWindow).on('hidden.bs.modal', function () {
      $('#' + idWindow).remove();
    });
  }

  /**
   * Возвращает виджет
   * @param {*} value - объект для получения значения
   *          fieldUid: поле, из которого будет получено значение
   *          documentUid: документ, из поля которого будет получено значение
   * @param {*} mode - form || view
   * @param {*} widget - поле, виджет которого будет получен, если параметр не передан используется value
   *          fieldUid: поле для виджета
   *          documentUid: документ для виджета  */
  static getFieldWidget(value, mode = 'view', widget = {}) {
    if (!value.fieldUid) {
      return "The field_uid is a required parameter";
    }

    let url = 'index.php?route=document/document/get_field_widget&field_uid=' + value.fieldUid;

    if (value.documentUid) {
      url += '&document_uid=' + value.documentUid;
    }
    url += '&mode=';
    if (mode !== 'form') {
      url += 'view';
    } else {
      url += 'form';
    }

    if (widget) {
      if (widget.fieldUid) {
        url += '&widget_field_uid=' + widget.fieldUid;
      }
      if (widget.documentUid) {
        url += '&widget_document_uid=' + widget.documentUid;
      }
      if (widget.doctypeUid) {
        url += '&widget_doctype_uid=' + widget.doctypeUid;
      }
    }
    return new Promise(function (resolve, reject) {
      $.ajax({
        url: url,
        dataType: 'html',
        success: function (html) {
          resolve(html);
        },
        error: function (e) {
          reject(e);
        }

      });

    });



  }

  static getNeighborHiddenElement($el) {
    let $result = $el.siblings('input.hidden');
    if ($result.length) {
      return $result;
    }
    $result = $el.siblings('input[type=hidden]');
    if ($result.length) {
      return $result;
    }
  }

  static getAutocompleteDoctype(multi = false) {
    return {
      'source': function (request, response) {
        $.ajax({
          url: 'index.php?route=doctype/doctype/autocomplete&filter_name=' + encodeURIComponent(request),
          dataType: 'json',
          cache: false,
          success: function (json) {
            // для множественной ссылки нужно будет реализовать проверку - если значение уже выбрано, не пускать его в автокомплит
            json.unshift({ doctype_uid: 0, name: Documentov.text.text_none });
            response($.map(json, function (item) {
              return { label: item['name'], value: item['doctype_uid'] };
            }));
          }
        });
      },
      'select': function (item) {
        if (multi) {
          //множественный выбор
          if (!item['value']) {
            return;
          }
          let $div = $(this).next().next();
          let name = $(this).attr('name').slice(0, -5);
          $($div).val('');
          $('#' + name + item['value']).remove();
          $($div).append('<div id="' + name + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="' + name + '[]" value="' + item['value'] + '" /></div>');
          $('input[name^=\'' + name + '\']').trigger('change');
        } else {
          //одиночный выбор
          let $hidden = Documentov.getNeighborHiddenElement($(this));
          if (item['value']) {
            $hidden.val(item['value']);
            $(this).val(stripTags(item['label']));

          } else {
            $hidden.val(0);
            $(this).val("");
          }
          $hidden.trigger('change');
          $(this).trigger('change');
        }

      }
    };
  }

  static getAjaxAutocompleteField(url, request, response) {
    return {
      url: url + '&filter_name=' + encodeURIComponent(request),
      dataType: 'json',
      cache: false,
      success: function (json) {
        json.unshift({ field_uid: 0, setting: 0, name: Documentov.text.text_none });
        response($.map(json, function (item) {
          if (item['setting'] == '1') {
            var label = '<i>' + item['name'] + '</i>';
          } else {
            var label = item['name'];
          }
          return { label: label, value: item['field_uid'] };
        }));
      }
    };
  }
  /**
   * Возвращает объект для автокомлита полей
   * @param {*} doctype - тип документа
   * @param {*} setting - 0 - обычные поля, 1 - настроечне, undefined - все
   */
  static getAutocompleteField(doctype, setting) {
    var url = 'index.php?route=doctype/doctype/autocomplete_field&doctype_uid=' + doctype;
    if (typeof setting !== "undefined") {
      url += '&setting=' + setting;
    }
    return {
      'source': function (request, response) {
        $.ajax(Documentov.getAjaxAutocompleteField(url, request, response));
      },
      'select': function (item) {
        let $hidden = Documentov.getNeighborHiddenElement($(this));
        if (item['value']) {
          $hidden.val(item['value']);
          let label = stripTags(item['label']);
          $(this).attr('title', label).val(label);

        } else {
          $hidden.val(0);
          $(this).attr('title', '').val("");
        }
        $hidden.trigger('change');
        $(this).trigger('change');
      }
    };
  }

  static reloadFieldWidget(selector, field_uid, field_value, hierarchy) {
    if (!field_uid) {
      $(selector).html("<input type='text' name=action" + hierarchy + "[value] value='" + decodeURIComponent(field_value) + "' class='form-control'/>");
      return;
    }
    var url = 'index.php?route=doctype/doctype/get_field_widget&field_uid=' + field_uid + '&widget_name=action' + hierarchy + '[value]';
    if (field_value) {
      url += '&field_value=' + encodeURIComponent(field_value);
    }
    $.ajax({ url: url, cache: false, dataType: 'html' }).done(function (html) {
      $(selector).html(html);
    }); //end ajax
  }

  static reloadFieldMethodList(selector, methodtype, field_uid, value, text) {
    if (!field_uid) {
      $(selector).html('');
      $(selector).append($('<option>', {
        value:
          'standard_getter',
        text: text
      }));
      $(selector).val = value;
      return;
    }
    var url = 'index.php?route=doctype/doctype/get_field_methods&field_uid=' + field_uid + '&method_type=' + methodtype;
    $.ajax({
      url: url,
      cache: false,
      dataType: 'json'
    }).done(function (data) {
      if (data) {
        data.unshift({
          name: 'standard_getter',
          alias: text
        });
      } else {
        data = [{ 'name': 'standard_getter', alias: text }];
      }
      $(selector).html('');
      data.forEach(function (item, i, data) {
        $(selector).append($('<option>', {
          value: item['name'],
          text: item['alias']
        }));
      });
      //$(selector).val(value);
    }); //end ajax
  }

  static getMessage() {
    let getCookie = function (name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(';').shift();
    }
    let message = {
      id: "",
      type: "",
      downloadLink: "",
      structureUID: getCookie("structure_uid"),
      sessionID: getCookie("OCSESSID"),
      signedFile: "",
      text: "",
    }
    return message
  }
}