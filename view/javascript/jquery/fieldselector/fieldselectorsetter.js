/*
	Field Selector with Setter plugin for jQuery
	Copyright (c) 2019 Andrey V Surov, Roman V Zhukov (documentov.com)
	Licensed under the Documentov license (https://documentov.com/license)
	Version: 0.1
*/
'use strict';
(function ($) {
  $.fn.fieldSelectorSetter = function (params) {
    let random = getRandom(1000000, 9999999);
    params = $.extend({
      name: 'name' + random, //имя без action[], например, field_uid
      prefixName: 'action[',
      postfixName: ']',
      attributeName: '', //при наличии data-атрибута, его значение будет добавляться к name
      doctypeUid: $('input[name=\'doctype_uid\']').val(), //если не передается тип документа, считаем, что плагин вызывается из админки журнала
      fieldUid: '', //выбранный ранее ИД поля
      fieldName: '', //название выбранного ранее поля
      setting: '0', //обычное поле 0, настроечное 1
      methodType: 'setter',
      method: '', //выбранный метод поля
      methodParams: [], //если передать false, то параметры метода не будут отображаться,
      methodParamsNameHierarchy: '',
      onlyStandardSetterParam: false, //если true вернет сеттеры только с одним параметром - "standard_setter_param"
    }, params);

    const blockSetterForm = 'block-method_form_' + random;

    return this.each(function () {
      let attr = '';
      if (params.attributeName) {
        attr = $(this).data(params.attributeName) || '';
      }
      const nameFieldUid = params.prefixName + params.name + attr + params.postfixName,
        nameFieldSetting = params.name + attr + '_setting',
        nameFieldSetter = params.prefixName + params.name + attr + '_method' + params.postfixName;
      let methodParamsNameHierarchy = '';
      if (params.methodParams) {
        methodParamsNameHierarchy = params.methodParamsNameHierarchy + '[' + params.name + attr + ']';//params.prefixName + params.name + attr + '_method_params' + params.postfixName;
      }
      let $elem = $(this);
      if (params.fieldName) {
        $elem.val(params.fieldName);
      }
      let $elemBefore = $elem;
      //скрытое поле для хранения ИД поля
      let $inputFieldUid = $('input[name="' + nameFieldUid + '"]');
      if (!$inputFieldUid.length) {
        $inputFieldUid = $('<input>').attr('type', 'hidden').attr('name', nameFieldUid).addClass('hidden').val(params.fieldUid);
        $elem.before($inputFieldUid);
      }
      $elemBefore = $inputFieldUid; //скрытое поле уже есть, добавлять lable будем перед ним
      //селекторы для выбора типа поля: обычное или настроечное
      let $setting = [];
      $setting[0] = $('<input>').attr('type', 'radio').attr('name', nameFieldSetting).attr('value', '0');
      $setting[1] = $('<input>').attr('type', 'radio').attr('name', nameFieldSetting).attr('value', '1');
      $setting[params.setting].attr('checked', 'checked');
      $elemBefore.before($('<label>').addClass('radio-inline').append($setting[0]).append(Documentov.text.text_macros_field.simple));
      $elemBefore.before($('<label>').addClass('radio-inline').append($setting[1]).append(Documentov.text.text_macros_field.setting));
      //форма сеттера
      let $blockSetterForm = $('<div>').attr('id', blockSetterForm);
      $elem.after($blockSetterForm);
      //селектор метода / сеттера
      let $selectorMethodName = $('<select>').attr('name', nameFieldSetter).addClass('form-control');
      $elem.after($selectorMethodName);

      //загрузка формы метода
      $selectorMethodName.on('change', function () {
        if (params.methodParams) {
          $blockSetterForm.html('');
          $.ajax({
            url: 'index.php?route=doctype/doctype/get_field_method_form&method=' + encodeURIComponent(this.value) + '&field_uid=' + $inputFieldUid.val() + '&method_params_name_hierarchy=' + methodParamsNameHierarchy + '&doctype_uid=' + params.doctypeUid,
            data: { method_params: params.methodParams },
            type: 'post',
            dataType: 'json',
            success: function (html) {
              if (html !== "") {
                $blockSetterForm.html(html);
              }
            }
          });
        }
      });
      //изменено поле
      $inputFieldUid.on('change', function () {
        //загружаем список геттеров
        if ($(this).val()) {
          let selector = this;
          $.ajax({
            url: 'index.php?route=doctype/doctype/get_field_methods&field_uid=' + $(this).val() + '&method_type=' + params.methodType,
            dataType: 'json',
            cache: false,
            success: function (json) {
              if (json) {
                json.unshift({
                  name: '',
                  alias: Documentov.text.text_macros_field.default_method
                });
              } else {
                json = [{
                  name: '',
                  alias: Documentov.text.text_macros_field.default_method
                }];
              }
              $selectorMethodName.empty();
              $.each(json, function () {
                if (this.params && params.onlyStandardSetterParam && (this.params.length > 1 || this.params[0] !== 'standard_setter_param')) {
                  return;
                }
                $selectorMethodName.append($('<option>', {
                  value: this.name,
                  text: this.alias
                }));
              });
              $blockSetterForm.html('');
              if (params.method) {
                $selectorMethodName.val(params.method);
                if (!$selectorMethodName.children('option:selected').length) {
                  $selectorMethodName.val('');
                }
              }
              $selectorMethodName.trigger('change');
            }
          });
        } else {
          $selectorMethodName.html('');
          $selectorMethodName.append($('<option>', {
            value: '',
            text: Documentov.text.text_macros_field.default_method
          }));
          $blockSetterForm.html('');
        }
      });
      $inputFieldUid.trigger('change');

      //автозавершение полей
      let autocomplete = Documentov.getAutocompleteField(params.doctypeUid, 0);
      autocomplete.source = function (request, response) {
        let doctypeUid = params.doctypeUid;
        let setting = $('input[name="' + nameFieldSetting + '"]:checked').val();
        if (setting == '1') {
          doctypeUid = '0';
        }
        let url = 'index.php?route=doctype/doctype/autocomplete_field&doctype_uid=' + doctypeUid + '&setting=' + setting;
        $.ajax(Documentov.getAjaxAutocompleteField(url, request, response));
      };
      $elem.autocomplete(autocomplete);
    });
  };
})(jQuery);