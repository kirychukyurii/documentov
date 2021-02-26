/*
	Language Input plugin for jQuery
	Copyright (c) 2019 Andrey V Surov, Roman V Zhukov (documentov.com)
	Licensed under the Documentov license (https://documentov.com/license)
	Version: 0.1
*/
(function ($) {
  $.fn.languageInput = function (params) {
    params = $.extend({
      name: 'languageInput',
      id: 'languageInput' + getRandom(),
      cssClass: 'form-control'
    }, params);
    let postfix = '';
    return this.each(function () {
      let $elem = $(this);
      let name = params.name + postfix;
      let id = params.id + postfix;
      let cssClass = params.cssClass;
      $button = $('<button>').attr('type', 'button').attr('data-toggle', 'dropdown')
        .addClass('btn btn-default dropdown-toggle');
      $ul = $('<ul>').attr('role', 'menu').addClass('dropdown-menu').css('left', 'auto');
      let i = 0;
      var $inputs = $('<span>').attr('id', id).addClass('language-input__text');
      $.each(Documentov.lang, function () {
        if (!i) {
          $button.append($('<img>').attr('src', '/language/' + this.code + '/' + this.code + '.png')).append(' ');
          $inputs.append($('<input>').attr('type', 'text').attr('name', `${name}[${this.language_id}]`).attr('data-lang', this.code).addClass(cssClass));
          i++;
        } else {
          $inputs.append($('<input>').attr('type', 'text').attr('name', `${name}[${this.language_id}]`).attr('data-lang', this.code).addClass(cssClass + ' hidden'));
        }
        $('<li>').append($('<a>').attr('data-lang', this.code).append($('<img>').attr('src', '/language/' + this.code + '/' + this.code + '.png')).append(' ' + this.name)).addClass('pointer').appendTo($ul);

      });
      $button.append($('<span>').addClass('caret'));
      $elem.append($button).append($ul).append($inputs).addClass('language-input');

      //переключение языка
      $elem.on('click', 'a', function (e) {
        let code = $(this).data('lang');
        let $img = $(this).parent().parent().parent().find('button img');
        $img.attr('src', '/language/' + code + '/' + code + '.png');

        $inputs.find('input').addClass('hidden');
        $inputs.find('[data-lang=\'' + code + '\']').removeClass('hidden');
      });
      postfix++;
    });
  };
})(jQuery);