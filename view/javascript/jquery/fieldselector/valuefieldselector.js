/*
  Value / Field Selector plugin for jQuery
  Выбор поля или ручной ввод значения; применяется для блочного элемента
	Copyright (c) 2019 Andrey V Surov, Roman V Zhukov (documentov.com)
	Licensed under the Documentov license (https://documentov.com/license)
	Version: 0.1
*/
(function ($) {
  $.fn.valueFieldSelector = function (params) {
    params = $.extend({
      namePrefix: '',
      nameInput: 'valueFieldSelector', //название инпута с полем / значением
      nameType: '', //чтобы было проще отличить, что выбрано в виджете (поле или руч знач) можно получать и проверять выбранный в селекторе тип
      namePostfix: '',
      value: '',
      valueId: '',
      type: 'field',
      doctypeUid: '',
      cssClassInput: 'form-control',
      cssClassType: 'btn btn-default'
    }, params);
    let nameInput = params.namePrefix + params.nameInput + params.namePostfix;
    let $selectorType = '';
    if (params.nameType) {
      let nameType = params.namePrefix + params.nameType + params.namePostfix;
      $selectorType = $('<input>').attr('type', 'hidden').attr('name', nameType).val(params.type);
      $(this).append($selectorType);

    }
    let changeType = function ($button, type, value = '', valueId = '') {
      $button.empty();
      let $spanInput = $button.parent().children('span');
      if (type == 'field') {
        $button.attr('title', Documentov.text.text_source_type_field).append($('<i>').addClass('fa fa-list-ul'));
        $spanInput.children('[type=hidden]').removeAttr('name').attr('name', nameInput).val(valueId);
        $spanInput.children('[type=text]').removeAttr('name').attr('title', value).val(value).autocomplete(Documentov.getAutocompleteField(params.doctypeUid, params.setting));
      } else {
        $button.attr('title', Documentov.text.text_source_type_manual).append($('<i>').addClass('fa fa-pencil'));
        $spanInput.children('[type=text]').attr('name', nameInput).attr('title', '').val(value).off();
        $spanInput.children('[type=hidden]').removeAttr('name').val('');
      }
      $button.append(' ').append($('<span>').addClass('caret'));
      if ($selectorType) {
        $selectorType.val(type);
      }
    };

    return this.each(function () {
      let $elem = $(this);
      let $button = $('<button>').attr('type', 'button').attr('data-toggle', 'dropdown').addClass('dropdown-toggle ' + params.cssClassType);

      let $blockInput = $('<span>');
      $('<input>').attr('type', 'hidden').addClass('hidden').appendTo($blockInput);
      $('<input>').attr('type', 'text').addClass(params.cssClassInput).appendTo($blockInput);

      let $ul = $('<ul>').attr('role', 'menu').addClass('dropdown-menu').css('left', 'auto');
      $('<li>').append($('<a>').attr('data-type', 'field').append($('<i>').addClass('fa fa-list-ul')).append(' ' + Documentov.text.text_source_type_field)).addClass('pointer').appendTo($ul);
      $('<li>').append($('<a>').attr('data-type', 'value').append($('<i>').addClass('fa fa-pencil')).append(' ' + Documentov.text.text_source_type_manual)).addClass('pointer').appendTo($ul);
      $elem.append($button).append($ul).append($blockInput).addClass('value-field-selector');
      changeType($button, params.type, params.value, params.valueId);

      //переключение типа вводимого значения
      $elem.on('click', 'a', function (e) {
        if ($(this).data('type')) {
          let $button = $(this).parent().parent().prev();
          changeType($button, $(this).data('type'));
        }
      });
    });
  };
})(jQuery);