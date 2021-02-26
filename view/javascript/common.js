'use strict';

function getURLVar(key, url) {
  var value = [];
  if (url) {
    var query = String(url).split('?');
  } else {
    var query = String(document.location).split('?');
  }


  if (query[1]) {
    var part = query[1].split('&');

    for (var i = 0; i < part.length; i++) {
      var data = part[i].split('=');

      if (data[0] && data[1]) {
        value[data[0]] = data[1];
      }
    }

    if (value[key]) {
      var result = value[key].split("#");
      return result[0];
    } else {
      return '';
    }
  }
}

function escapeHtml(string) {
  var entityMap = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
    '/': '&#x2F;',
    '`': '&#x60;',
    '=': '&#x3D;'
  };
  return String(string).replace(/[&<>"'`=\/]/g, function (s) {
    return entityMap[s];
  });
}

function unEscapeHtml(string) {
  var entityMap = {
    '&amp;': '&',
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#39;': "'",
    '&#x2F;': '/',
    '&#x60;': '`',
    '&#x3D;': '='
  };
  return String(string).replace(/[\&amp;|\&lt;|\&gt;|\&quot;|\&#39;|\&#x2F;|\&#x60;|\&#x3D;]/g, function (s) {
    return entityMap[s];
  });
}

function stripTags(string) {
  return string.replace(/(?!(\<br\>|\<br\s\/\>))<\/?[^>]+>/g, '');
}

function addScript(script, type = 'text/javascript') {
  $('<script>').attr('src', script + '?v=' + Documentov.text.VERSION).attr('type', type).appendTo($("head"));
}

function addStyle(style) {
  $('<link>').attr('rel', 'stylesheet').attr('type', 'text/css').attr('href', style + '?v=' + Documentov.text.VERSION).appendTo($("head"));
}

//Клонирует шаблон idTemplate (с #) внуть $element (jQuery)
function getTemplateContent(idTemplate) {
  let $templateCondition = $(idTemplate);
  return $templateCondition[0].content.cloneNode(true);

}

function getRandom(min = 1000000000, max = 9999999999) {
  return Math.floor(Math.random() * (max - min) + min);
}


function alert(message) {
  Documentov.showModalWindow('alertModal', 'Documentov', message, 'alertModalOK', 'md', false, 'OK');
}


function confirm(message, callback) {
  Documentov.showModalWindow('confirmModal', 'Documentov', message, 'confirmModalOK', 'md', true, Documentov.text.text_yes, Documentov.text.text_no);
  $('#confirmModalOK').on('click', callback);
}


$(document).ready(function () {

  // new Documentov();

  document.addEventListener('scroll', function (event) {
    if (event.target.id === 'tcolumn') { // баг бутстрапа - если дробдаун в оверфлоу-блоке при скролле список фиксирован и не перемещается
      var inpt;
      $.each($('#tcolumn .dropdown-menu'), function () {
        inpt = $(this).parent().find(".dropdown-toggle");

        if (typeof inpt.position() !== undefined) {
          $(this).css('top', inpt.position().top + inpt.outerHeight(true));
        }

      });

    }
  }, true);
  //    

  $('body').on('click', function (e) {
    //открытие внешних ссылок в новой вкладке
    var href;
    if (e.target.tagName.toUpperCase() == 'A') {
      if ($(e.target).parents('.note-editable').length) { //если ссылка размещена в редакторе summernote
        return;
      }
      href = $(e.target).attr('href');
    } else { //внутри ссылки может быть, например, span
      $.each($(e.target).parents(), function () {
        if (this.tagName.toUpperCase() == 'A') {
          href = $(this).attr('href');
          return;
        }
      });
    }
    if (href && href.search(/http(s)?:\/\//) === 0 && href.indexOf(location.hostname) < 0) {
      //ссылка абсолютная и не содержит наше доменное имя
      e.preventDefault();
      window.open(href, '_target');
      return;
    }
    if (href && window.navDocumentov && (getURLVar('folder_uid') || getURLVar('route') == 'document/search') && !getURLVar('noinfolder', href)) {
      //открывается ссылка через журнал, если ссылка на документ, открываем ее через навигатор
      var document_uid = getURLVar('document_uid', href);
      if (document_uid) {
        e.preventDefault();
        navDocumentov.openDocument(document_uid);
        $('.modal').modal('hide');
      }
    }
  });

  // tooltips on hover
  $('[data-toggle=\'tooltip\']').tooltip({ container: 'body' });

  // Makes tooltips work on ajax generated content
  $(document).ajaxStop(function () {
    $('[data-toggle=\'tooltip\']').tooltip({ container: 'body', placement: 'auto' });
  });

  $(document).on('show.bs.tooltip', function () {
    // скрывает подсказки 
    $('.tooltip').not(this).hide();
  });

  // Image Manager
  $(document).on('click', 'a[data-toggle=\'image\']', function (e) {
    var $element = $(this);
    var $popover = $element.data('bs.popover'); // element has bs popover?

    e.preventDefault();

    // destroy all image popovers
    $('a[data-toggle="image"]').popover('destroy');

    // remove flickering (do not re-add popover when clicking for removal)
    if ($popover) {
      return;
    }

    $element.popover({
      html: true,
      placement: 'right',
      trigger: 'manual',
      content: function () {
        return '<button type="button" id="button-image" class="btn btn-primary"><i class="fa fa-pencil"></i></button> <button type="button" id="button-clear" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>';
      }
    });

    $element.popover('show');

    $('#button-image').on('click', function () {
      var $button = $(this);
      var $icon = $button.find('> i');

      $('#modal-image').remove();

      $.ajax({
        url: 'index.php?route=common/filemanager&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
        dataType: 'html',
        beforeSend: function () {
          $button.prop('disabled', true);
          if ($icon.length) {
            $icon.attr('class', 'fa fa-circle-o-notch fa-spin');
          }
        },
        complete: function () {
          $button.prop('disabled', false);

          if ($icon.length) {
            $icon.attr('class', 'fa fa-pencil');
          }
        },
        success: function (html) {
          $('body').append('<div id="modal-image" class="modal">' + html + '</div>');

          $('#modal-image').modal('show');
          $('#modal-image').draggable({ handle: '.modal-header' });
        }
      });

      $element.popover('destroy');
    });

    $('#button-clear').on('click', function () {
      $element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

      $element.parent().find('input').val('').trigger('change');

      $element.popover('destroy');
    });
  });
});


// Autocomplete */
(function ($) {
  $.fn.autocomplete = function (option) {
    return this.each(function () {
      this.timer = null;
      this.items = new Array();

      $.extend(this, option);

      $(this).attr('autocomplete', 'off');

      // Focus
      $(this).on('focus', function () {
        this.request();
      });

      // Blur
      $(this).on('blur', function () {
        setTimeout(function (object) {
          object.hide();
        }, 200, this);
      });

      // Keydown
      $(this).on('keydown', function (event) {
        switch (event.keyCode) {
          case 27: // escape
            this.hide();
            break;
          default:
            this.request();
            break;
        }
      });

      // Click
      this.click = function (event) {
        event.preventDefault();
        //чтобы в списке для каждого элемента можно было использовать тег
        //для форматирования отдельных элементов списка
        if ($(event.target).parent()[0].tagName === "A") {
          var value = $(event.target).parent().parent().attr('data-value');
        } else {
          var value = $(event.target).parent().attr('data-value');
        }

        if (value && this.items[value]) {
          //                        this.items[value]['label'] = stripTags(this.items[value]['label']);


          let label = this.items[value]['label'];
          if (label.indexOf('img src=') >= 0) {
            //в списке изображение
            let image_url = label.match(/<img src=["'](.*?)['"]>/)[1];
            if (image_url) {
              $(this).attr('style', 'background: url(' + image_url + ') no-repeat center left;background-position-x:1%;padding-left:5%;');
            }
            this.items[value]['label'] = label.replace(/<img src=["'].*['"]>/, '');
          } else {
            $(this).removeAttr('style');
          }
          this.select(this.items[value]);
          $(this).blur();
        }
      };

      // Show
      this.show = function (count) {
        var pos = $(this).position();
        var clHeight = Math.floor(document.documentElement.clientHeight / 2.8);
        var height_dropdown = count * 37 < clHeight ? count * 37 : clHeight;
        height_dropdown = height_dropdown <= 74 ? 79 : height_dropdown;

        if ((document.documentElement.clientHeight > ($(this).offset().top + $(this).outerHeight() + height_dropdown))
          || $(this).parent().parent().hasClass('dropdown-menu')
        ) {
          //меню вниз
          $(this).siblings('ul.dropdown-menu').css({
            top: pos.top + $(this).outerHeight(),
            left: pos.left,
            'max-height': height_dropdown,
            height: 'none',
            width: $(this).outerWidth()
          });
        } else {
          height_dropdown = count * 32 < clHeight ? count * 32 : clHeight;
          height_dropdown = height_dropdown <= 64 ? 74 : height_dropdown;

          $(this).siblings('ul.dropdown-menu').css({
            top: pos.top - height_dropdown - 4,
            left: pos.left,
            'height': height_dropdown + 'px',
            width: $(this).outerWidth(),
            'max-height': 'none',
          });

        }
        $(this).siblings('ul.dropdown-menu').show();
      };

      // Hide
      this.hide = function () {
        $(this).siblings('ul.dropdown-menu').hide();
      };

      // Request
      this.request = function () {
        clearTimeout(this.timer);

        this.timer = setTimeout(function (object) {
          object.source($(object).val(), $.proxy(object.response, object));
        }, 200, this);
      };

      // Response
      this.response = function (json) {
        var html = '';
        if (json.length) {
          for (var i = 0; i < json.length; i++) {
            this.items[json[i]['value']] = json[i];
          }

          for (var i = 0; i < json.length; i++) {
            if (!json[i]['category']) {
              html += '<li data-value="' + json[i]['value'] + '"><a title="' + stripTags(json[i]['label']) + '" href="#">' + json[i]['label'] + '</a></li>';
            }
          }

          // Get all the ones with a categories
          var category = new Array();

          for (var i = 0; i < json.length; i++) {
            if (json[i]['category']) {
              if (!category[json[i]['category']]) {
                category[json[i]['category']] = new Array();
                category[json[i]['category']]['name'] = json[i]['category'];
                category[json[i]['category']]['item'] = new Array();
              }

              category[json[i]['category']]['item'].push(json[i]);
            }
          }

          for (i in category) {
            html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

            for (j = 0; j < category[i]['item'].length; j++) {
              html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a title="' + stripTags(category[i]['item'][j]['label']) + '" href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
            }
          }
        }

        if (html) {
          this.show(json.length);
        } else {
          this.hide();
        }

        $(this).siblings('ul.dropdown-menu').html(html);
      };

      $(this).after('<ul class="dropdown-menu"></ul>');
      $(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

    });
  };
})(window.jQuery);
