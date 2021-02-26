'use strict';

$(document).ready(function () {
  $('[data-toggle=\'summernote\']').each(function () {
    loadSummernote();
    var element = this;
    $(element).summernote({
      lang: languageCode,
      disableDragAndDrop: true,
      height: 300,
      emptyPara: '',
      codemirror: {// codemirror options
        mode: 'text/html',
        htmlMode: true,
        lineNumbers: true,
        theme: 'monokai'
      },
      fontsize: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '30', '36', '48', '64'],
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
        ['fontname', ['fontname']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'image']],
        ['view', ['fullscreen', 'codeview', 'help']]
      ],

      buttons: {
        image: function () {
          var ui = $.summernote.ui;
          // create button
          var button = ui.button({
            contents: '<i class="note-icon-picture" />',
            //                        tooltip: $.summernote.lang[$.summernote.options.lang].image.image,
            tooltip: $.summernote.lang[languageCode].image.image,
            click: function () {
              $('#modal-image').remove();

              $.ajax({
                url: 'index.php?route=common/filemanager',
                dataType: 'html',
                beforeSend: function () {
                  $('#button-image i').replaceWith('<i class="fa fa-circle-o-notch fa-spin"></i>');
                  $('#button-image').prop('disabled', true);
                },
                complete: function () {
                  $('#button-image i').replaceWith('<i class="fa fa-upload"></i>');
                  $('#button-image').prop('disabled', false);
                },
                success: function (html) {
                  $('body').append('<div id="modal-image" class="modal">' + html + '</div>');

                  $('#modal-image').modal('show');
                  $('#modal-image').draggable({ handle: '.modal-header' });

                  $('#modal-image').delegate('a.thumbnail', 'click', function (e) {
                    e.preventDefault();

                    $(element).summernote('insertImage', $(this).attr('href'));

                    $('#modal-image').modal('hide');
                  });
                }
              });
            }
          });

          return button.render();
        }
      }
    });
  });


  $('[data-toggle=\'summernote_s\']').each(function () {
    // init_summernote_s(this);
  });

});

var template_vars = [];
var route = getURLVar('route');
if (route) {
  if (route.indexOf('doctype') > -1 || route.indexOf('extension') > -1) { //запрос выполняется только для админ части
    $.ajax({
      url: 'index.php?route=doctype/doctype/autocomplete_var',
      dataType: 'json',
      async: false,
      cache: false,
      success: function (json) {
        $.each(json, function () {
          template_vars.push(this);
        });
      }
    });
  }
}

function loadSummernote() {
  if (!$.summernote) {
    addStyle('view/javascript/codemirror/lib/codemirror.css');
    addStyle('view/javascript/codemirror/theme/monokai.css');
    addStyle('view/javascript/summernote/summernote.css?v=1');
    addStyle('view/javascript/codemirror/theme/monokai.css');
    addScript('view/javascript/codemirror/lib/codemirror.js');
    addScript('view/javascript/codemirror/lib/xml.js');
    addScript('view/javascript/codemirror/lib/formatting.js');
    addScript('view/javascript/summernote/summernote.js?v=1');

  }
}


function init_summernote_s(element, doctype_uid, params) {
  loadSummernote();
  if (doctype_uid === undefined) {
    doctype_uid = getURLVar('doctype_uid');
  }
  var languageCode = Documentov.languageCode.split('-')[0];
  var init = {
    lang: languageCode,
    disableDragAndDrop: true,
    height: 300,
    emptyPara: '',
    codemirror: {// codemirror options
      mode: 'text/html',
      htmlMode: true,
      lineNumbers: true,
      lineWrapping: true,
      theme: 'monokai'
    },
    fontsize: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '30', '36', '48', '64'],
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'italic', 'underline', 'clear']],
      ['fontname', ['fontname']],
      ['fontsize', ['fontsize']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link', 'image', 'video']],
      ['view', ['fullscreen', 'codeview', 'help']],
      ['fields', ['fields']],
      ['variables', ['variables']],
      ['conditions', ['conditions']]
    ],
    callbacks: {
      onBlur: function () {
        autosave();
      }
    },
    buttons: {
      fields: function (context) {
        var ui = $.summernote.ui;
        var event = ui.buttonGroup([
          ui.button({
            contents: ' ' + $.summernote.lang[languageCode].documentov.fields + ' <i class="fa fa-caret-down" aria-hidden="true"></i>',
            tooltip: $.summernote.lang[languageCode].documentov.fields,
            data: {
              toggle: 'dropdown'
            },
            click: function () {
              context.invoke('saveRange');
            }
          }),
          ui.dropdown({
            contents: function () {
              return '<div class="btn-group"><input type="text" class="input-select_field" value="" placeholder="Поле" class="form-control"></div>';
            },
            callback: function (items) {
              $(items).find('input').autocomplete({
                'source': function (request, response) {
                  $.ajax({
                    url: 'index.php?route=doctype/doctype/autocomplete_field&filter_name=' + encodeURIComponent(request) + '&doctype_uid=' + doctype_uid,
                    dataType: 'json',
                    cache: false,
                    success: function (json) {
                      response($.map(json, function (item) {
                        return {
                          label: item['name'],
                          value: item['field_uid']
                        };
                      }));
                    }
                  });
                },
                'select': function (item) {
                  context.invoke('restoreRange');
                  context.invoke("editor.insertText", '\{\{ ' + item['label'] + ' \}\}');
                }
              });
            }
          })
        ]);

        return event.render();   // return button as jquery object
      },
      variables: function (context) {
        var ui = $.summernote.ui;
        var event = ui.buttonGroup([
          ui.button({
            contents: ' ' + $.summernote.lang[languageCode].documentov.variables + ' <i class="fa fa-caret-down" aria-hidden="true"></i>',
            tooltip: $.summernote.lang[languageCode].documentov.variables,
            data: {
              toggle: 'dropdown'
            }
          }),
          ui.dropdown({
            items: template_vars,
            callback: function (items) {
              $(items).find('li a').on('click', function (e) {
                context.invoke("editor.insertText", '{{ ' + $(this).html() + ' }}');
                e.preventDefault();
              });

            }
          })
        ]);

        return event.render();   // return button as jquery object
      },
      conditions: function () {
        var ui = $.summernote.ui;
        let ind = 1;
        // create button
        var button = ui.button({
          contents: $.summernote.lang[languageCode].documentov.conditions,
          tooltip: $.summernote.lang[languageCode].documentov.conditions_tooltip,
          click: function () {
            //списки полей и кнопок доктайпа
            let doctypeFields = [];
            let doctypeFieldsTmpl = [];
            let $editor = $('textarea[name=\'' + $(element).attr('name') + '\'] + .note-editor .note-editable');
            $.ajax({
              url: 'index.php?route=doctype/doctype/autocomplete_field&doctype_uid=' + doctype_uid + '&filter_name=',
              dataType: 'json',
              async: false,
              cache: false,
              success: function (fields) {
                let tmpl = $editor.html();
                fields.forEach(function (field) {
                  doctypeFields.push(field);
                  let regexp = new RegExp('\{\{\ ?' + field.name + '\ ?\}\}', 'gm');
                  if (regexp.test(tmpl)) {
                    doctypeFieldsTmpl.push(field);
                  }
                });
              }
            });

            var editorIds = [];
            //получаем все элементы из редактора
            $editor.find('*').each(function (a, el) {
              if (el.id) { //если у элемента есть идентификатор
                editorIds.push({
                  id: '#' + el.id,
                  name: $.summernote.lang[languageCode].documentov.id + ' ' + el.id
                });
              }
            });
            $.ajax({
              url: 'index.php?route=doctype/doctype/autocomplete_button&doctype_uid=' + doctype_uid + '&filter_name=',
              dataType: 'json',
              async: false,
              cache: false,
              success: function (buttons) {
                buttons.forEach(function (button) {
                  editorIds.push({
                    id: '#button' + button.button_uid,
                    name: $.summernote.lang[languageCode].documentov.button + ' ' + button.name
                  });
                });
              }
            });

            //массив идентификаторов


            var template_conditions_name = $(element).attr('name').replace('template', 'template_conditions');
            //добавляем кнопки сохранения и отмены, если шаблон для формы
            if (template_conditions_name.indexOf('form') > -1) {
              editorIds.push({
                id: '#button-save_document',
                name: $.summernote.lang[languageCode].documentov.button + ' ' + $.summernote.lang[languageCode].documentov.save
              });
              editorIds.push({
                id: '#button-cancel_document',
                name: $.summernote.lang[languageCode].documentov.button + ' ' + $.summernote.lang[languageCode].documentov.cancel
              });
            } else if (template_conditions_name.indexOf('view') > -1) { //добавляем кнопки из маршрута для шаблона формы
            }
            var $modalBody = $('<div>');
            var $buttonModalBody = $('<span>').attr('id', 'modal-button_add_js_condition').addClass('btn btn-default').append($('<i>').addClass('fa fa-plus-circle').html($.summernote.lang[languageCode].documentov.add_condition));
            var $formModalBody = $('<div>').attr('id', 'modal-block_js_conditions').addClass('form-horizontal');
            $modalBody.append($buttonModalBody).append($formModalBody);
            Documentov.showModalWindow('modal-js_conditions', $.summernote.lang[languageCode].documentov.conditions, $modalBody, 'modal-button_save_js_conditions');

            $('#modal-button_add_js_condition').on('click', function () {
              var js_condition_id = ind++;
              $(this).data('js_condition_id', js_condition_id);

              //--------------- БЛОК УСЛОВИЙ --------------------------//
              var block_inner_condition = function (js_condition_id) {
                var inner_condition_id = 0; //внутренняя нумерация условий
                var num = 0; //количество внутренних условий
                $('input[name^=\'js_conditions[' + js_condition_id + '][condition]\']').each(function () {
                  if ($(this).attr('name').split('][')[2] >= inner_condition_id) {
                    inner_condition_id = ++$(this).attr('name').split('][')[2];
                  }
                  num++;
                });
                var html =
                  '       <div class="row" data-id="' + inner_condition_id + '">';
                var col = 4; //количество столбцов для поля
                if (num) {
                  col--;
                  html +=
                    '           <div class="col-sm-2 col-md-1 padding-3">' +
                    '               <select name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][join]" class="mini-select">' +
                    '                   <option value="and">' + $.summernote.lang[languageCode].documentov.and.toUpperCase() + '</option>' +
                    '                   <option value="or">' + $.summernote.lang[languageCode].documentov.or.toUpperCase() + '</option>' +
                    '               </select>' +
                    '           </div>';
                }
                html +=
                  '           <div class="col-sm-' + col + ' col-md-' + col + ' padding-3">' +
                  '               <select name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][field_uid]" class="form-control input-sm">';
                doctypeFields.forEach(function (field) {
                  html +=
                    '                   <option value="' + field.field_uid + '">' + field.name + '</option>';
                });

                html +=
                  '               </select>' +
                  // '               <input type="text" name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][field_uid]" value="" class="hidden">' +
                  // '               <input type="text" name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][field_name]" value="" class="form-control input-sm">' +
                  '           </div>' +
                  '           <div class="col-sm-3 col-md-3 padding-3">' +
                  '               <select name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][comparison]" class="form-control input-sm">' +
                  '                   <option value="equal">' + $.summernote.lang[languageCode].documentov.equal + '</option>' +
                  '                   <option value="not_equal">' + $.summernote.lang[languageCode].documentov.not_equal + '</option>' +
                  '                   <option value="consist">' + $.summernote.lang[languageCode].documentov.consist + '</option>' +
                  '                   <option value="not_consist">' + $.summernote.lang[languageCode].documentov.not_consist + '</option>' +
                  '                   <option value="equal_field">' + $.summernote.lang[languageCode].documentov.equal_field + '</option>' +
                  '                   <option value="not_equal_field">' + $.summernote.lang[languageCode].documentov.not_equal_field + '</option>' +
                  '               </select>' +
                  '           </div>' +
                  '           <div class="col-sm-3 col-md-4 padding-3">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][value_id]" class="hidden">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][condition][' + inner_condition_id + '][value_value]" class="form-control input-sm">' +
                  '           </div>' +
                  '           <div class="col-sm-1 padding-3 text-right">' +
                  '               <div class="dropdown">' +
                  '                   <button class="btn btn-default btn-xs dropdown-toggle dropdown-button-picture" type="button" data-toggle="dropdown" title="' + $.summernote.lang[languageCode].documentov.actions + '"><i class="hidden-sm hidden-xs fa fa-cog"></i><span class="caret"></span></button>' +
                  '                   <ul class="dropdown-menu">' +
                  '                       <li><a data-uid="' + js_condition_id + '" data-action="add" data-type="condition" class="pointer">' + $.summernote.lang[languageCode].documentov.add_condition + '</a></li>' +
                  '                       <li><a data-uid="' + js_condition_id + '" data-action="del" data-type="condition" class="pointer">' + $.summernote.lang[languageCode].documentov.del_condition + '</a></li>' +
                  '                   </ul>' +
                  '               </div>' +
                  '           </div>' +
                  '       </div>';
                return html;
              };
              //--------------- БЛОК ДЕЙСТВИЙ -------------------------//
              var block_inner_action = function (js_condition_id) {
                var result = "";
                var inner_action_id = 0; //внутренняя нумерация условий
                $('select[name^=\'js_conditions[' + js_condition_id + '][action]\']').each(function () {
                  if ($(this).attr('name').split('][')[2] >= inner_action_id) {
                    inner_action_id = ++$(this).attr('name').split('][')[2];
                  }
                });
                result = '' +
                  '       <div class="row">' +
                  '           <div class="col-sm-4 padding-3">' +
                  '               <select name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][action]" data-type="action" data-condition_id="' + js_condition_id + '" data-inner_action_id="' + inner_action_id + '" class="form-control input-sm">' +
                  '                   <option value="hide">' + $.summernote.lang[languageCode].documentov.hide + '</option>' +
                  '                   <option value="show">' + $.summernote.lang[languageCode].documentov.show + '</option>' +
                  '                   <option value="record">' + $.summernote.lang[languageCode].documentov.record + '</option>' +
                  '                   <option value="download">' + $.summernote.lang[languageCode].documentov.download + '</option>' +
                  '               </select>' +
                  '           </div>' +
                  '           <div class="col-sm-7 padding-3">' +
                  '             <div id="modal-block_js_conditions-action_arg_select">' +
                  '               <select name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][argument]" class="form-control input-sm">';
                doctypeFieldsTmpl.forEach(function (field) {
                  result +=
                    '                   <option value=".field_block_' + field.field_uid + '">' + $.summernote.lang[languageCode].documentov.field + ' ' + field.name + '</option>';
                });
                $.each(editorIds, function () {
                  if (this.id) {
                    result +=
                      '                   <option value="' + this.id + '">' + this.name + '</option>';
                  }
                });
                result +=
                  '               </select>' +
                  '             </div>' +
                  '             <div  id="modal-block_js_conditions-' + js_condition_id + '-action_' + inner_action_id + '_arg_template" style="display: none;">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][template]" data-type="template" class="form-control input-sm js_conditions-action_arg_template">' +
                  '             </div>' +
                  '             <div  id="modal-block_js_conditions-' + js_condition_id + '-action_' + inner_action_id + '_arg_current_doctype_field" style="display: none;">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][current_doctype_field_uid]" class="hidden">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][current_doctype_field_name]" data-type="current_doctype_field" placeholder="' + $.summernote.lang[languageCode].documentov.download_document_field + '" class="form-control input-sm js_conditions-action_arg_current_doctype_field">' +
                  '             </div>' +
                  '             <div  id="modal-block_js_conditions-' + js_condition_id + '-action_' + inner_action_id + '_arg_selected_doctype_field" style="display: none;">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][selected_doctype_field_uid]" class="hidden">' +
                  '               <input type="text" name="js_conditions[' + js_condition_id + '][action][' + inner_action_id + '][selected_doctype_field_name]" data-type="selected_doctype_field" placeholder="' + $.summernote.lang[languageCode].documentov.download_field + '" class="form-control input-sm js_conditions-action_arg_selected_doctype_field">' +
                  '             </div>' +
                  '           </div>' +
                  '           <div class="col-sm-1 padding-3 text-right">' +
                  '               <div class="dropdown">' +
                  '                   <button class="btn btn-default btn-xs dropdown-toggle dropdown-button-picture" type="button" data-toggle="dropdown" title="' + $.summernote.lang[languageCode].documentov.actions + '"><i class="hidden-sm hidden-xs fa fa-cog"></i><span class="caret"></span></button>' +
                  '                   <ul class="dropdown-menu">' +
                  '                       <li><a data-uid="' + js_condition_id + '" data-action="add" data-type="action" class="pointer">' + $.summernote.lang[languageCode].documentov.add_action + '</a></li>' +
                  '                       <li><a data-uid="' + js_condition_id + '" data-action="del" data-type="action" class="pointer">' + $.summernote.lang[languageCode].documentov.del_action + '</a></li>' +
                  '                   </ul>' +
                  '               </div>' +
                  '           </div>' +
                  '       </div>';

                return result;
              };
              //----------------- ОБРАБОТЧИКИ ОКНА УСЛОВИЯ ----------------- //
              var js_condition_event_listeners = function (js_condition_id) {
                $('div[data-uid=\'' + js_condition_id + '\'] input[name*=\'value_value\']').autocomplete({
                  'source': function (request, response) {
                    //проверяем условие - если равно/не равно полю, то используем автокомплит
                    var selector = $(this).parent().parent().find('select[name*=\'comparison\']');
                    if (selector.val().indexOf('field') < 0) {
                      return; //в сравнении выбран ручной ввод, автокомплит не нужен
                    }
                    $.ajax({
                      url: 'index.php?route=doctype/doctype/autocomplete_field&filter_name=' + encodeURIComponent(request) + '&doctype_uid=' + doctype_uid,
                      dataType: 'json',
                      cache: false,
                      success: function (json) {
                        response($.map(json, function (item) {
                          return {
                            label: item['name'],
                            value: item['field_uid']
                          };
                        }));
                      }
                    });
                  },
                  'select': function (item) {
                    $(this).val(item['label']);
                    $(this).attr('title', item['label']);
                    $(this).prev('input.hidden').val(item['value']);
                  }
                });
                //----------------- ДОБАВЛЕНИЕ СТРОКИ УСЛОВИЯ / ДЕЙСТВИЯ ----------------- //
                $('a[data-uid=\'' + js_condition_id + '\'][data-action=\'add\']').off('click');
                $('a[data-uid=\'' + js_condition_id + '\'][data-action=\'add\']').on('click', function () {
                  var inner_condition_block = $(this).parent().parent().parent().parent().parent();
                  var html = '';
                  if ($(this).data('type') == 'condition') {
                    html = block_inner_condition(js_condition_id);
                  } else {
                    html = block_inner_action(js_condition_id);
                  }
                  $(inner_condition_block).after(html);
                  js_condition_event_listeners(js_condition_id);
                });
                //----------------- УДАЛЕНИЕ СТРОКИ УСЛОВИЯ / ДЕЙСТВИЯ ----------------- //
                $('a[data-uid=\'' + js_condition_id + '\'][data-action=\'del\']').off('click');
                $('a[data-uid=\'' + js_condition_id + '\'][data-action=\'del\']').on('click', function () {
                  var row = $(this).parent().parent().parent().parent().parent().parent();
                  //действие
                  if (row.children().length < 2) {
                    //удаляем полностью условие
                    if ($(this).data('type') == 'condition') {
                      //удаление условия
                      $(row).parent().remove();
                    } else {
                      //удаление действия
                      $(row).find('.form-control').each(function () { $(this).val(''); });
                    }
                  } else {
                    //удаляем текущую строку условия
                    $(this).parent().parent().parent().parent().parent().remove();
                    //удаляется первая строка условия? 
                    if ($(this).data('type') == 'condition' && $(row).children().length && $(row).children(':first').children().length == 5) {
                      //у первой строки нужно удалить join
                      $(row).children(':first').children(':first').remove();
                      //изменяем класс у столбца для поля, увеличивая его ширину на удаленный join
                      $(row).children(':first').children(':first').removeClass('col-sm-3 col-md-3');
                      $(row).children(':first').children(':first').addClass('col-sm-4 col-md-4');
                    }

                  }
                });
                //----------------- ИЗМЕНЕНИЕ УСЛОВИЯ / ДЕЙСТВИЯ ----------------- //
                $('#modal-block_js_conditions select').off('change');
                $('#modal-block_js_conditions select').on('change', function () {
                  $(this).attr('title', $("option:selected", this).text());
                  if ($(this).data('type') === 'action') {
                    //изменено действие, в зависимости от выбранного действия показывает виджет для установки аргумента / аргументов
                    var $modalBlockTemplate = $('#modal-block_js_conditions-' + $(this).data('condition_id') + '-action_' + $(this).data('inner_action_id') + '_arg_template');
                    var $modalBlockCurrentDoctypeField = $('#modal-block_js_conditions-' + $(this).data('condition_id') + '-action_' + $(this).data('inner_action_id') + '_arg_current_doctype_field');
                    var $modalBlockSelectedDoctypeField = $('#modal-block_js_conditions-' + $(this).data('condition_id') + '-action_' + $(this).data('inner_action_id') + '_arg_selected_doctype_field');
                    switch ($(this).val()) {
                      case 'record':
                        $modalBlockTemplate.show();
                        $modalBlockCurrentDoctypeField.hide();
                        $modalBlockSelectedDoctypeField.hide();
                        break;
                      case 'download':
                        $modalBlockTemplate.hide();
                        $modalBlockCurrentDoctypeField.show();
                        $modalBlockSelectedDoctypeField.show();
                        break;
                      default:
                        $modalBlockTemplate.hide();
                        $modalBlockCurrentDoctypeField.hide();
                        $modalBlockSelectedDoctypeField.hide();
                        break;
                    }
                  }
                });
                //----------------- КЛИК ПО СТРОКЕ ВВОДА ШАБЛОНА ------------------- //
                $('.js_conditions-action_arg_template').on('click', function (e) {
                  if ($('#modal-js_conditions_action_template').length) {
                    return; //почему-то при клике на инпут, событие генерируется дважды (Crome), поэтому проверяем на наличие открытого окна 
                  }
                  var $input = $(this);
                  //показываем модальное окно с редактором шаблона
                  var $div_modal_dialog_content_body = $('<div>').addClass('modal-body');
                  var $div_modal_dialog_content_body_toolbar = $('<div>').addClass('');
                  $div_modal_dialog_content_body_toolbar.append($('<div>').addClass('col-sm-6 padding-3').
                    append($('<input>').attr('id', 'js_conditions-action_arg_template_field_uid').addClass('hidden')).
                    append($('<input>').attr('id', 'js_conditions-action_arg_template_field').attr('placeholder', $.summernote.lang[languageCode].documentov.field).addClass('form-control input-sm')));
                  var $div_modal_dialog_content_body_toolbar_sgn = $('<div>').addClass('col-sm-6 padding-3 btn-group');
                  var elems = ['+', '-', '*', '/', '(', ')'];
                  elems.forEach(function (sgn) {
                    $div_modal_dialog_content_body_toolbar_sgn.append($('<button>').addClass('btn btn-default btn-sm js_conditions-action_arg_template_button').val(sgn).html(sgn));
                  });
                  $div_modal_dialog_content_body_toolbar.append($div_modal_dialog_content_body_toolbar_sgn);
                  $div_modal_dialog_content_body.append($div_modal_dialog_content_body_toolbar);
                  $div_modal_dialog_content_body.append($('<textarea>').addClass('modal-md').css('width', '100%').css('box-sizing', 'border-box').val($input.val()));
                  Documentov.showModalWindow('modal-js_conditions_action_template', $.summernote.lang[languageCode].documentov.recordTemplate,
                    $div_modal_dialog_content_body, 'js_conditions-action_arg_template_save', 'md');

                  var $template = $('#modal-js_conditions_action_template textarea');

                  // обработчик нажатия на кнопку операций
                  $('.js_conditions-action_arg_template_button').on('click', function () {
                    insertIntoAreaTxt($(this).val());
                  });
                  //обработчик автокомплита полей
                  $('#js_conditions-action_arg_template_field').autocomplete(Documentov.getAutocompleteField(doctype_uid));
                  //выбрано поле, вставляем его в текстарию шаблона
                  $('#js_conditions-action_arg_template_field_uid').on('change', function () {
                    var field = $('#js_conditions-action_arg_template_field').val();
                    if (field) {
                      insertIntoAreaTxt('{{ ' + field + ' }}');
                    }
                    $('#js_conditions-action_arg_template_field').val('');
                    $('#js_conditions-action_arg_template_field_uid').val('');
                  });
                  //сохранение окна
                  $('#js_conditions-action_arg_template_save').on('click', function () {
                    $input.val($template.val()).attr('title', $template.val());
                    $('#modal-js_conditions_action_template').modal('hide');
                  });

                  function insertIntoAreaTxt(text) {
                    var caretPos = $template[0].selectionStart;
                    var textAreaTxt = $template.val();
                    $template.val(textAreaTxt.substring(0, caretPos) + text + textAreaTxt.substring(caretPos));
                    $template.focus();
                  }
                });
                //----------------- АВТОКОМПЛИТ СТРОКИ ВЫБОРА ПОЛЯ ТЕКУЩЕГО ДОКТАЙПА -- //
                $('.js_conditions-action_arg_current_doctype_field[autocomplete!=off]').autocomplete(Documentov.getAutocompleteField(doctype_uid));
                //----------------- АВТОКОМПЛИТ СТРОКИ ВЫБОРА ПОЛЯ ЛЮБОГО ДОКТАЙПА  -- //
                $('.js_conditions-action_arg_selected_doctype_field[autocomplete!=off]').autocomplete(Documentov.getAutocompleteField(0));
              };
              //----------------- КОНЕЦ ШАБЛОНА ЗАПИСИ --------------------- //
              //----------------- СОДЕРЖИМОЕ СТРОКИ УСЛОВИЯ ---------------- //
              var js_condition_block =
                '<div class="form-group shadow2 margin-bottom-15" data-uid="' + js_condition_id + '">' +
                '   <div class="col-sm-6 col-xs-6">' + block_inner_condition(js_condition_id) +
                '   </div>' +
                '   <div class="col-sm-6 col-xs-6">' + block_inner_action(js_condition_id) +
                '   </div>' +
                '</div>';
              $('#modal-block_js_conditions').append(js_condition_block);
              js_condition_event_listeners(js_condition_id);

            });
            //----------------- СОХРАНЕНИЕ ОКНА УСЛОВИЙ ----------------- //

            $('#modal-button_save_js_conditions').on('click', function () {
              var nameParam = '';
              var result = {};
              $('#modal-js_conditions input[type=\'text\'], #modal-js_conditions[type=\'hidden\'], #modal-js_conditions select').each(function () {
                nameParam = this.name.slice(14, -1).split('][');
                if (!result[nameParam[0]]) {
                  result[nameParam[0]] = {};
                }
                if (!result[nameParam[0]][nameParam[1]]) {
                  result[nameParam[0]][nameParam[1]] = {};
                }
                if (!result[nameParam[0]][nameParam[1]][nameParam[2]]) {
                  result[nameParam[0]][nameParam[1]][nameParam[2]] = {};
                }
                result[nameParam[0]][nameParam[1]][nameParam[2]][nameParam[3]] = this.value.replace(/['`]/g, '\'');
              });
              $('input[name=\'' + template_conditions_name + '\']').val(JSON.stringify(result));
              $('input[name=\'' + template_conditions_name + '\']').trigger('change');
            });
            //----------------- ИНИЦИАЛИЗАЦИЯ ОКНА УСЛОВИЙ ----------------= //
            var saved_conditions = $('input[name=\'' + template_conditions_name + '\']').val();
            if (saved_conditions && saved_conditions !== "null") {
              $.each(JSON.parse(saved_conditions, (k, v) => {
                if (typeof v == 'string') {
                  return v.replace(/&#039;/g, "'");
                }
                return v;
              }), function () {
                var js_condition_id = $('#modal-button_add_js_condition').trigger('click').data('js_condition_id'); //идентификатор условия
                if (this.condition) {
                  var i = 0;
                  $.each(this.condition, function () {
                    if (i > 0) {
                      $('a[data-uid=\'' + js_condition_id + '\'][data-type=\'condition\'][data-action=\'add\']:last').trigger('click');
                    }
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[field_uid\]\']').val(this.field_uid);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[field_name\]\']').val(this.field_name);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[field_name\]\']').attr('title', this.field_name);
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[comparison\]\']').val(this.comparison);
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[comparison\]\']').attr('title', $('select[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[comparison\]\'] option:selected').text());
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[value_value\]\']').val(this.value_value);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[value_value\]\']').attr('title', this.value_value);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[value_id\]\']').val(this.value_id);
                    if (this.join) {
                      $('select[name=\'js_conditions\[' + js_condition_id + '\]\[condition\]\[' + i + '\]\[join\]\']').val(this.join);
                    }
                    i++;
                  });
                }
                if (this.action) {
                  var i = 0;
                  $.each(this.action, function () {
                    if (i > 0) {
                      $('a[data-uid=\'' + js_condition_id + '\'][data-type=\'action\'][data-action=\'add\']:last').trigger('click');
                    }
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[action\]\']').val(this.action);
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[action\]\']').attr('title', $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[action\]\'] option:selected').text());
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[argument\]\']').val(this.argument);
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[argument\]\']').attr('title', $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[argument\]\'] option:selected').text());
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[template\]\']').val(this.template).attr('title', this.template);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[current_doctype_field_name\]\']').val(this.current_doctype_field_name).attr('title', this.current_doctype_field_name);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[current_doctype_field_uid\]\']').val(this.current_doctype_field_uid);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[selected_doctype_field_name\]\']').val(this.selected_doctype_field_name).attr('title', this.selected_doctype_field_name);
                    $('input[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[selected_doctype_field_uid\]\']').val(this.selected_doctype_field_uid).attr('title', this.template);
                    $('select[name=\'js_conditions\[' + js_condition_id + '\]\[action\]\[' + i + '\]\[action\]\']').trigger('change');
                    i++;
                  });
                }
              });
            }
          }
        });

        return button.render();
      },
      image: function () {
        var ui = $.summernote.ui;
        var button = ui.button({
          contents: '<i class="note-icon-picture" />',
          tooltip: $.summernote.lang[languageCode].image.image,
          click: function () {
            $('#modal-image').remove();

            $.ajax({
              url: 'index.php?route=common/filemanager',
              dataType: 'html',
              beforeSend: function () {
                $('#button-image i').replaceWith('<i class="fa fa-circle-o-notch fa-spin"></i>');
                $('#button-image').prop('disabled', true);
              },
              complete: function () {
                $('#button-image i').replaceWith('<i class="fa fa-upload"></i>');
                $('#button-image').prop('disabled', false);
              },
              success: function (html) {
                $('body').append('<div id="modal-image" class="modal">' + html + '</div>');

                $('#modal-image').modal('show');
                $('#modal-image').draggable({ handle: '.modal-header' });

                $('#modal-image').delegate('a.thumbnail', 'click', function (e) {
                  e.preventDefault();

                  $(element).summernote('insertImage', $(this).attr('href'));

                  $('#modal-image').modal('hide');
                });
              }
            });
          }
        });

        return button.render();
      }
    }
  };
  if (params && params.hideConditions) {
    delete init.toolbar.conditions;
    delete init.buttons.conditions;
  }
  $(element).summernote(init);
}


