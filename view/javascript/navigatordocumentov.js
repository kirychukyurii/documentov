function NavigatorDocumentov(params) {
  for (prop in params) {
    this[prop] = params[prop];
  }
  //    if (params.folderUid !== undefined) {
  //        //инициализируется журнал
  //    }
  this.tableParams = '';
  this.filterParams = '';
  this.inputSearch = '';
  this.historyCache = []; //история открытия документов для работы кнопки Назад
  this.historyCacheActual = 1; //признак актуальности кэша (действие может сбросить его)
  this.buttons = {};
  this.tfields = [];
  this.numFilter = 0;
  this.time_fade_out = 0;
  this.filterId = 0;
  this.dataReplaceBlocks = [];
  this.sort = '';
  this.order = '';
  this.page = 1;
  this.ajaxError = 0; //количество ошибочных запросов Ajax
  this.timeout = 150;
  this.contentBlock; //идентификатор блока, в который загружаются документы
  this.fieldDraft = {}; //объект для хранения значения полей документа для черновика
  this.searchType = 'quick';
  this.prevSearchType = '';
  this.callbacks = {};
  //убираем ошибки, если они были выведены ранее
  $('.alert-danger').remove();
  if ($('div').is('#tcolumn')) {
    this.contentBlock = $('#tcolumn');
    html = '&nbsp; <span class="btn btn-default" onclick="navDocumentov.loadTableCache();"><i class="fa fa-arrow-left"></i></span>&nbsp;';
  } else {
    this.contentBlock = $('#document_form');
  }
  window.onresize = function () {
    let h = $('body').height() - $('.sticky').outerHeight(true) - $('#main_menu').outerHeight(true) - $('footer').outerHeight(true);
    $(navDocumentov.contentBlock).height(h);
    $('#grouping').height(h);
  };
  window.onpopstate = function () {
    navDocumentov.loadTableCache();
  };
}

NavigatorDocumentov.prototype.buttonAction = function (button) {
  let nav = this;
  var button_uid = $(button).data('button_uid');

  //получаем тип кнопки: folder - кнопка стандартного журнала, document - кнопка документа
  var button_type = $(button).data('button_type');

  if (!button_uid || !button_type || $(button).attr('disabled')) {
    return false;
  }
  let selected_docs = '';
  if (button_type === 'folder') {
    //если тип кнопки folder необходимо получить выбранные документы
    selected_docs = $('#tcolumn input[type=\'checkbox\']:checked');
    //и записать данные журнала, если истории еще нет
    if (!nav.historyCache.length) {
      nav.historyCache.push({
        workspace: $('#tcolumn').html(),
        toolbar: $('#folder_toolbar').html(),
        document_uid: '0'
      });
    }
  }
  let url = 'index.php?route=document/' + button_type + '/button&button_uid=' + button_uid;

  if ($(button).data('document_uid')) {
    url += '&document_uid=' + $(button).data('document_uid');
  }
  if (nav.folderUid) {
    url += '&folder_uid=' + nav.folderUid;
  }
  $.ajax({
    url: url,
    type: 'post',
    data: selected_docs,
    cache: false,
    dataType: 'json',
    beforeSend: function (xhr) {
      $('#block_loading').show();
      $(button).attr('disabled', 'disabled');
    },
    complete: function () {
      if (!nav.errorNetwork(this) && !nav.ajaxError) {
        $(button).removeAttr('disabled');
      }
    },
    success: function (json) {
      $(button).removeAttr('disabled');
      nav.historyCacheActual = 0; //сбрасываем актуальность кэша, т.к. было выполнено некое действие и документы могли измениться            
      nav.answerServer(json);
      nav.ajaxError = 0;
    },
    error: function (xhr) {
      nav.ajaxError++;
      if (xhr.status && nav.ajaxError) {
        nav.showWindow(nav.language.textHeaderError, nav.language.textError + ': ' + xhr.status + ' ' + nav.parseError(xhr.responseText));
        nav.ajaxError = 0;
      }

    },
  });
  this.runCallbacks('buttonaction');
};

NavigatorDocumentov.prototype.parseError = function (textError) {
  let regexp = /(.*?)<\/b>{"/m;
  let rclear = /<\/?b>/gim;
  let error = textError.match(regexp);
  if (error && error.length > 1) {
    return escapeHtml(error[1].replace(rclear, ''));
  }
  return escapeHtml(textError.replace(rclear, ''));
};

NavigatorDocumentov.prototype.openDocument = function (document_uid) {
  if (!document_uid || document_uid == "0") {
    return;
  }
  document_uid = document_uid.slice(0, 36);
  let nav = this;
  if (!nav.historyCache.length) {
    nav.historyCache.push({
      workspace: $('#tcolumn').html(),
      toolbar: $('#folder_toolbar').html(),
      document_uid: '0',
      title: document.title,
      scroll: $('#tcolumn').scrollTop()
    });
    nav.historyCacheActual = 1;
  } else if (nav.historyCache.length == 1) {
    nav.historyCache[0].workspace = $('#tcolumn').html();
    nav.historyCache[0].toolbar = $('#folder_toolbar').html();
    nav.historyCache[0].scroll = $('#tcolumn').scrollTop();
  }
  nav.loadUrl('index.php?route=document/document/get_document&document_uid=' + document_uid);
  var url = window.location.href;
  var arr = url.split("&document_uid=");
  var arr2 = arr[0].split('route=');
  var ajax_url = 'index.php?route=account/account/set_lastpage&controller=document/folder&controller=' + arr2[1];

  if (document_uid !== '0') {
    history.pushState({ document_uid: document_uid }, null, arr[0] + '&document_uid=' + document_uid);
    ajax_url += '&document_uid=' + document_uid;
  } else {
    history.pushState(null, null, arr[0]);
  }

  $.ajax({
    url: ajax_url
  });
  this.runCallbacks('opendocument');
};

NavigatorDocumentov.prototype.showWindow = function (title, text) {
  let html = '    <div id="windowNavigatorDocumentov" class="modal fade" role="dialog">' +
    '        <div class="modal-dialog">' +
    '            <div class="modal-content">' +
    '                <div class="modal-header">' +
    '                    <button type="button" class="close" data-dismiss="modal">&times;</button>' +
    '                        <h4 class="modal-title">' + title + '</h4>' +
    '                </div>' +
    '                <div class="modal-body" id="block-nav_window_body" style="overflow:auto;">' + text + '<hr>\n\n' + this.language.textVersion + '<br>\n' + this.language.textURL + ': ' + location +
    '                </div>' +
    '                <div class="modal-footer" id="block-nav_window_footer">' +
    '                    <button type="button" class="btn btn-default" data-dismiss="modal">' + this.language.buttonClose + '</button>';
  if (text.indexOf("index.php?route=account/login") < 0) { //если не страница аутентификации выводим кнопки сообщения об ошибке
    html += '                    <button type="button" class="btn btn-default" onclick="navDocumentov.prepareReport()">' + this.language.buttonBugReport + '</button>';
  }
  html +=
    '                </div>' +
    '            </div>' +
    '        </div>' +
    '    </div>';
  $('#windowNavigatorDocumentov').remove();
  $('body').append(html);
  $('#block_loading').hide();
  $('#windowNavigatorDocumentov').modal('show');
  $('#windowNavigatorDocumentov').draggable({ handle: '.modal-header' });
},
  NavigatorDocumentov.prototype.prepareReport = function () {
    let htmlb = '<form action="https://www.documentov.com/report.php" method="post" enctype="multipart/form-data" id="form-doctype" class="form-horizontal">' +
      '<small><b>' + this.language.textBugReport + ':</b></small><br><textarea readonly name="text_nav_report_error" class="form-control">' + $('#block-nav_window_body').html() +
      '</textarea><br>' +
      '<small><b>' + this.language.textBugReportAdd + ':</small></b>' +
      '<br><textarea name="text_nav_report_detail" class="form-control"></textarea></br>' +
      '<small><b>' + this.language.textBugReportEmail + ':</small></b>' +
      '<br><input type="text" name="text_nav_report_email" class="form-control">' +
      '<br><button type="submit" class="btn btn-default">' + this.language.buttonSendReport + '</button>' +
      '</form>';
    ;
    let htmlf = '';
    $('#block-nav_window_body').html(htmlb);
    $('#block-nav_window_footer').html(htmlf);
  },
  NavigatorDocumentov.prototype.errorNetwork = function (param) {
    let nav = this;
    if (nav.ajaxError) {
      nav.ajaxErrorState = true;
      $('#block_loading').show();
      if (nav.ajaxError > 50) {
        $('#block_loading').html(nav.language.textLoadingError3);
        nav.ajaxError = 0;
        return false;
      }
      if (nav.ajaxError < 20) {
        $('#block_loading').html(nav.language.textLoadingError1);
      } else {
        $('#block_loading').html(nav.language.textLoadingError2);
      }
      setTimeout(function () {
        $.ajax(param);
      }, nav.ajaxError * 150);
    } else {
      nav.ajaxError = 0;
      $('#block_loading').html(nav.language.textLoading);
      //            $('#block_loading').hide();
    }
  };

NavigatorDocumentov.prototype.errorAjax = function (xhr) {
  this.ajaxError++;
  if (xhr.status && this.ajaxError) {
    let error;
    if (xhr.responseText.indexOf('Array') > 0) {
      this.showWindow(this.language.textHeaderError, this.language.textError + ': ' + xhr.status + ' ' + this.parseError(xhr.responseText));
    } else {
      this.showWindow(this.language.textHeaderError, this.language.textError + ': ' + xhr.status + ' ' + xhr.responseText);
    }
    this.ajaxError = 0;
  }
};

NavigatorDocumentov.prototype.answerServer = function (json) {
  let nav = this;
  let html;
  //скрываем подсказки кнопок
  $('.tooltip').hide();
  if (json) {
    //убираем ошибки, если они были выведены ранее
    $('.alert-danger').remove();
    if ($('div').is('#tcolumn')) {
      html = '&nbsp; <span class="btn btn-default" onclick="navDocumentov.loadTableCache();"><i class="fa fa-arrow-left"></i></span>&nbsp;';
    }
    //с сервера могут вернуться:
    // * append - код, который нужно добавить к текущей странице
    // * window - код для отображения в модальном окне
    // * error - текст ошибки для отображения пользователю
    // * replace - массив идентификаторов элементов, содержимое которых нужно заменить на текущей странице
    // * redirect - перенаправить на другую страницы через json или перезагрузкой страницы; если адрес редиректа 
    //              начинается с location:, то  только перезагрузка
    // * reload - перезагрузить таблицу документов // актуально только для журнала
    // * document_uid - если установлен, то этот документ записывается в адресную строку браузера

    if (json['append'] && json['append'].length > 0) {
      json['append'].map(function (e) {
        if (Array.isArray(e)) {
          $('body').append((e.reverse()).join());
        } else {
          $('body').append(e);
        }
      });
    }
    if (json['error']) {
      $('#block_loading').hide();
      $('.alert').remove();
      if (!$('div').is('#folder_toolbar')) {
        $('#document_toolbar').append('<div class="alert alert-danger">' + json['error'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
      } else {
        $('#folder_toolbar').append('<div class="alert alert-danger">' + json['error'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
      }
    } else if (json['window']) {
      $('#modal-action').remove();
      html = '<div id="modal-action" class="modal fade">';
      html += '  <div class="modal-dialog modal-lg">';
      html += '    <div class="modal-content">';
      html += json['window'];
      html += '    </div>';
      html += '  </div>';
      html += '</div>';
      $('body').append(html);
      $('#modal-action').modal('show');
      $('#modal-action').draggable({ handle: '.modal-header' });
      $('#block_loading').hide();
    } else if (json['reload']) {
      if (nav.folderUid) {
        nav.historyCacheActual = 0;
        nav.loadTableCache();
      } else {
        window.location.replace(json.reload);
      }
    } else if (json['replace'] && Object.keys(json['replace']).length > 0) {
      $('#block_loading').hide();
      nav.dataReplaceBlocks = [];
      nav.historyCacheActual = 0;
      $.each(json['replace'], function (id, content) {
        nav.dataReplaceBlocks[id] = $('#' + id).html();
        $('#' + id).html(content);
        $('#' + id).show();
      });
    } else if (json['redirect']) {
      $('#block_loading').hide();
      if (json['redirect'].indexOf('location:') >= 0) {
        //жесткий редирект
        let address_location = json['redirect'].split('location:');
        // $('#tcolumn').fadeOut(nav.timeout, function () {
        //   $('#tcolumn').fadeIn(nav.timeout);
        // });
        // $('#folder_toolbar').fadeOut(nav.timeout, function () {
        //   $('#folder_toolbar').fadeIn(nav.timeout);
        // });
        // $('#document_toolbar').fadeOut(nav.timeout, function () {
        //   $('#document_toolbar').fadeIn(nav.timeout);
        // });

        let win = window.open(address_location[1], '_blank');
        if (!win) {
          alert(nav.language.textBlockPopupWindow);
        }

      } else if (json['redirect'].indexOf(window.location.hostname) > -1 &&
        json['redirect'].indexOf('document_uid=') > -1 && nav.folderUid) {
        nav.loadUrl(json['redirect'] + '&folder_uid=' + nav.folderUid);
      } else {
        if (json['append']) {
          //к текущей страницы добавили контент, проверяем на открытое модальное окно
          if ($('body').hasClass('modal-open')) { //делаем редирект после закрытия окна
            $(document).on('hidden.bs.modal', '.modal', function () {
              if ($('.modal:visible').length < 1) { //2=>1 конт Удаление -> move -> message
                window.location.href = json['redirect'];
              }
            });
          } else {
            window.location.href = json['redirect'];
          }
        } else {
          window.location.href = json['redirect'];
        }
      }
    } else {
      $(nav.contentBlock).hide();
      $('#folder_toolbar').hide;

      $('#document_toolbar').remove();
      if ($('div').is('#folder_toolbar')) { //проверка именно на наличие для случая с плиточным журналом - в журнале тулбара нет, но див есть для закрепления тулбара
        $('#folder_toolbar').html(json['toolbar']);
        $('#document_toolbar').show();
        $('#folder_toolbar').fadeIn(nav.timeout);
      } else {
        $(nav.contentBlock).prepend(json['toolbar']);
      }
      $('#document_toolbar').prepend(html);
      $('#block_loading').hide();
      $(nav.contentBlock).html(json['form']);
      $(nav.contentBlock).fadeIn(nav.timeout, function () {
        $('#block_loading').hide();
      });
      $(nav.contentBlock).animate({ scrollTop: 0 }, "slow");

      if (json['append']) {
        $.each(json['append'].reverse(), function () {
          $('body').append(this);
        });
      }
    }

    if (json['document_uid']) {
      nav.historyCache.push({
        workspace: $(nav.contentBlock).html(),
        toolbar: $('#folder_toolbar').html(),
        document_uid: json['document_uid'],
        title: json['title']
      });
      var url = window.location.href;
      var arr = url.split("&document_uid=");
      history.pushState({ document_uid: json['document_uid'] }, null, arr[0] + '&document_uid=' + json['document_uid']);
    }
    if (json['title']) {
      document.title = json['title'];
    }
    $(window).trigger('resize');
  }

};

NavigatorDocumentov.prototype.loadUrl = function (url) {
  let nav = this;
  $.ajax({
    url: encodeURI(url),
    dataType: 'json',
    cache: false,
    beforeSend: function (xhr) {
      $('#block_loading').show();
    },
    success: function (json) {
      nav.answerServer(json);
      nav.ajaxError = 0;
    },
    error: function (xhr) {
      nav.ajaxError++;
      if (xhr.status && nav.ajaxError) {
        nav.showWindow(nav.language.textHeaderError, nav.language.textError + ': ' + nav.parseError(xhr.responseText));
        nav.ajaxError = 0;
      }
    },
    complete: function () {
      if (nav.ajaxError) {
        nav.errorNetwork(this);
      }
    },

  });
};

NavigatorDocumentov.prototype.loadTable = function (params, node) {
  let nav = this;
  let toolbar = '';
  if (nav.hideToolbar == 'hide') {
    $('#folder_toolbar').hide();
  }

  if (nav.historyCache.length > 0) { //0 а не 1 - Структура -> Нов док -> нажать на подар слева
    //нажатие на группу из открытого документа; нужно будет перегрузить тулбар
    toolbar = nav.historyCache[0].toolbar;
  }
  nav.historyCache = [];
  $('.tooltip').hide();
  // $('#tcolumn').fadeOut(0);
  $('#document_toolbar').fadeOut(0);
  var add_url = '';
  if (nav.sort !== '') {
    add_url = '&sort=' + nav.sort + '&order=' + nav.order;
  }
  if (nav.inputSearch) {
    add_url += '&search=' + nav.inputSearch;
    if (nav.searchType === 'fulltext') {
      add_url += '&search_type=fulltext';
    } else {
      add_url += '&search_type=quick';
    }
  }

  add_url += '&page=' + nav.page + '&limit=' + nav.limit;
  $.ajax({
    url: 'index.php?route=document/folder/get_documents&folder_uid=' + nav.folderUid + '&' + encodeURI(params) + encodeURI(nav.filterParams) + encodeURI(add_url),
    dataType: 'json',
    cache: false,
    beforeSend: function (xhr) {
      $('#block_loading').show();
    },
    success: function (json) {
      if (json['redirect']) {
        window.location.href = json['redirect'];
      }
      if (json['error']) {
        html = '<div class="alert alert-danger">' + json['error'] + '</div>';
        $('#block_loading').hide();
        $('#tcolumn').html(html).show();
        return;
      }
      nav.tfields = json['fields'];
      html = '<div>' +
        '   <table class="table table-bordered table-hover shadow3 table-sticky">' +
        '       <thead class="background1"><tr>';
      if (nav.hideToolbar != 'hide' && !nav.hideSelectors) {
        html += '           <th style="width: 1px;" class="text-center no-print"><input type="checkbox" id="selected_all" onclick="navDocumentov.selectedDocuments();" /></th>';
      }

      $.each(json['fields'], function () {
        //поля
        if (this.tcolumn_hidden == "1") {
          return;
        }
        html += '       <th class="text-left"';
        if (this.tcolumn_width) {
          html += ' style="width:' + this.tcolumn_width + ';"';
          // html += ' width="' + this.tcolumn_width + '"';
        }
        html += '><a onclick="navDocumentov.sortField(\'' + this.field_uid + '\');" class="pointer';
        if (json['sort'] === this.field_uid) { //по данному полю вкл сортировка
          html += ' ' + json['order'];
          sort = json['sort'];
          order = json['order'];
        }
        html += '">' + this.tcolumn_name + '</a></th>';

      });
      html += '</tr></thead><tbody>';
      $.each(json['documents'], function (index, document) {
        html += '<tr class="animated fadeIn fast" ';
        if (typeof json['filter_documents'][document['document_uid']] !== undefined && json['filter_documents'][document['document_uid']] !== null) {
          let color = '';
          let background = '';
          let font_weight = '';
          let font_style = '';
          let text_decoration = '';
          $.each(nav.filters, function () {
            if ($.inArray(this.filterId, json['filter_documents'][document['document_uid']]) >= 0) {
              if (this.action == 'style') {
                if (this.params.color) {
                  color = '#' + this.params.color;
                }
                if (this.params.background) {
                  background = '#' + this.params.background;
                }
              }
              if (this.action == 'font') {
                if (this.params.font.bold) {
                  font_weight = 'bold';
                }
                if (this.params.font.italic) {
                  font_style = 'italic';
                }
                if (this.params.font.linethrough) {
                  text_decoration = 'line-through';
                }
              }
            }
          });
          if (color || background || font_weight || font_style || text_decoration) {
            html += ' style="' + (color ? 'color:' + color + ';' : '') +
              (background ? 'background-color:' + background + ';' : '') +
              (font_weight ? 'font-weight:' + font_weight + ';' : '') +
              (font_style ? 'font-style:' + font_style + ';' : '') +
              (text_decoration ? 'text-decoration:' + text_decoration + ';' : '') +
              '"';
          }
        }
        if (json['document_root'] && json['document_root'] == document['document_uid']) {
          //это название группы, н-р, подразделение, содержимое кот. отображаем
          html += ' class="well"';
        }
        html += '>';
        if (nav.hideToolbar != 'hide' && !nav.hideSelectors) {
          html += '<td class="text-center no-print"><input type="checkbox" name="selected[]" onclick="navDocumentov.selectedDocument(this);" value="' + document['document_uid'] + '" /></td>';
        }

        $.each(json['fields'], function (index, field) {
          //вывод документов
          if (field.tcolumn_hidden != "1") {
            html += ' <td class="text-left" style="cursor:pointer;" data-document_uid=\'' + document['document_uid'] + '\'" onclick="navDocumentov.openDocument(\'' + document['document_uid'] + '\');">' + document['v' + field['field_uid'].replace(/-/g, '')].replace('&amp;', '&') + '</td>';
          }
        });

        html += '</tr>';
      });
      //итоговая строка
      if (json['total_columns']) {
        var html_footer = "<tr class='active'><td></td>";
        var isfooter = 0;
        $.each(json['fields'], function (index, field) {
          if (field.tcolumn_hidden == "1") {
            return;
          }
          var vfield = 'v' + field['field_uid'].replace(/-/g, '');
          if (json['total_columns'][vfield]) {
            html_footer += "<td><b>" + json['total_columns'][vfield] + "</b></td>";
            isfooter = 1;
          } else {
            html_footer += "<td></td>";
          }
        });
        html_footer += "</tr>";
        if (isfooter) {
          html += html_footer;
        }
      }
      html += '</tbody></table>';
      html += '</div>';
      //пагинация
      html += '<div class="folder-pagination"><div class="col-sm-9 text-left"> ' + json['pagination'] + '</div><div class="col-sm-3 text-right form-inline">' + json['text_total_documents'] + ' ' + json['total_documents'] + ' ' + json['text_show_documents'];
      html += '<select name="limit" onchange="navDocumentov.changeLimit(this);" class="form-control">';
      $.each(nav.limits, function () {
        html += '<option value="' + this + '"';
        if (nav.limit == this) {
          html += ' selected="selected"';
        }
        html += '>' + this + '</option>';
      });
      html += '</select>';
      if (!nav.groupingTree) {
        html += '<br><br>&nbsp;';   //без группировки нижняя панель наезжает на таблицу                 
      }
      html += '</div>';
      $('#tcolumn').html(html);
      nav.tableParams = json['reload_url'];
      $('#button_filter').removeAttr('disabled');
      $('#button_search').removeAttr('disabled');
      if (toolbar) {
        $('#folder_toolbar').html(toolbar);
      }
      $('#document_toolbar').fadeIn(nav.timeout);
      $('#tcolumn').fadeIn(nav.timeout);
      if (node !== undefined && node !== '' && json['children_path'] !== undefined) {
        //передана активная нода, удаляем всех детей и добавляем полученные
        node.removeChildren();
        $.each(json['children_path'], function () {
          // var key = this.value ? encodeURIComponent(this.value) : encodeURIComponent(this.title);
          var key = this.value ? this.value : encodeURIComponent(this.title);
          var title = this.title ? this.title : nav.language.textEmptyValue;
          var fid2 = this.fid2;
          var fid = this.fid;
          var gtid = this.gtid;
          node.addChildren({
            title: title,
            folder: true,
            key: (gtid ? '&gtid=' + gtid : '') + (fid ? '&fid=' + fid + '&value=' + key : '') + (fid2 ? params + '&fid2=' + fid2 + '&value2=' + key : '')
          });
        });
        node.sortChildren();
        node.toggleExpanded();
        nav.historyCacheActual = 1;
      }
      if (json['documents']) {
        documentUIDs = [];
        json['documents'].forEach(function (item) {
          documentUIDs.push(item['document_uid'])
        })
        $.ajax({
          url: 'index.php?route=document/folder/get_avail_buttons&folder_uid=' + nav.folderUid,
          type: 'post',
          data: { documentUIDs: documentUIDs },
          dataType: 'json',
          success: function (btns) {
            $.each(btns, function (buttonUID, documentUIDs) {
              nav.buttons[buttonUID] = documentUIDs;
              if (documentUIDs.length == 1 && documentUIDs[0] == "0") {
                $('#folder_button' + buttonUID).removeAttr('disabled');
              }
            });
          }
        });
      }

      // if (json['buttons']) {
      //   $.each(json['buttons'], function (button_uid, documents) {
      //     // if (documents) {
      //     nav.buttons[button_uid] = documents;
      //     // }
      //   });
      // }
      $("#tcolumn").animate({ scrollTop: 0 }, "slow");
      nav.ajaxError = 0;
      nav.historyCache = [];
      $('#block_loading').hide();
    },
    error: function (xhr) {
      nav.ajaxError++;
      if (xhr.status && nav.ajaxError) {
        nav.showWindow(nav.language.textHeaderError, nav.language.textError + ': ' + nav.parseError(xhr.responseText));
        nav.ajaxError = 0;
      }
    },
    complete: function () {
      if (nav.ajaxError) {
        nav.errorNetwork(this);
      }
    },
  });
  //из-за замены тулбара стирается значение быстрого поиска, восстанавливаем
  $('input[name=\'inputSearch\']').val(nav.inputSearch);
  if (nav.inputSearch) {
    $('#button_search').addClass('btn-info');
  }
  var url = window.location.href;
  var arr = url.split("&document_uid=");
  history.pushState(null, null, arr[0]);
  $.ajax({
    url: 'index.php?route=account/account/set_lastpage&controller=document/folder&folder_uid=' + nav.folderUid + '&' + encodeURI(params) + encodeURI(nav.filterParams) + encodeURI(add_url)
  });

};

NavigatorDocumentov.prototype.loadTableCache = function (loc) {
  let url = window.location.href;
  let arr = url.split("&document_uid=");
  let nav = this;
  while (nav.historyCache.length > 1 &&
    (nav.historyCache[nav.historyCache.length - 1].document_uid === arr[1] ||
      nav.historyCache[nav.historyCache.length - 1].workspace === '')) {
    nav.historyCache.pop();
  }
  if (nav.historyCache[0] && nav.historyCache[0]['toolbar']) {
    $('#folder_toolbar').html(nav.historyCache[0]['toolbar']);
  }
  // if (nav.historyCache.length < 1 || !nav.historyCache[0]['workspace']) { //проверка на workspace нужна при обновлении страницы журнала с открытым документом - грузится только док, но не таблица с целью экономии времени
  if (nav.historyCache.length < 1 || (nav.historyCache.length < 2 && !nav.historyCache[0]['workspace'])) { //проверка на workspace нужна при обновлении страницы журнала с открытым документом - грузится только док, но не таблица с целью экономии времени
    let $tblp = "";
    if (nav.tableParams !== undefined) {
      $tblp = nav.tableParams;
    }
    //восстановление панели действий журнала
    if (nav.historyCache[0] && nav.historyCache[0]['toolbar']) {
      $('#folder_toolbar').html(nav.historyCache[0]['toolbar']);
      document.title = nav.historyCache[nav.historyCache.length - 1].title;
    }
    if (nav.historyCache[0] && nav.historyCache[0]['scroll']) {
      // восстанавливаем скролл
      $('#tcolumn').animate({ scrollTop: 0 }, "slow");
    }
    nav.loadTable($tblp, '');
  } else if (!nav.historyCacheActual) {
    let document_uid = nav.historyCache[nav.historyCache.length - 1].document_uid;
    //восстановление панели действий журнала            
    if (nav.historyCache[0]) {
      if (nav.historyCache[0]['toolbar']) {
        $('#folder_toolbar').html(nav.historyCache[0]['toolbar']);
      }

      if (nav.historyCache[0]['title']) {
        document.title = nav.historyCache[0].title;
      }
    }
    nav.historyCache.pop();
    if (document_uid !== '0' && document_uid) {
      nav.openDocument(document_uid);
      //nav.historyCache.pop(); - может сбросить 0 запись кеша с тулбаром журнала
    } else {
      nav.loadTable(nav.tableParams);
    }
  } else {
    if (nav.historyCache[nav.historyCache.length - 1].document_uid && nav.historyCache[nav.historyCache.length - 1].document_uid != arr[1]) {
      history.replaceState({ document_uid: nav.historyCache[nav.historyCache.length - 1].document_uid }, null, arr[0] + '&document_uid=' + nav.historyCache[nav.historyCache.length - 1].document_uid);
    }
    document.title = nav.historyCache[nav.historyCache.length - 1].title;
    if (nav.historyCache.length < 2 && nav.hideToolbar === 'hide') {
      //загружаем из кэша журнал со скрытым тулбаром
      $('#folder_toolbar').hide();
      $('#tcolumn').html(nav.historyCache[nav.historyCache.length - 1].workspace);
      // });
    } else {
      $('#folder_toolbar').fadeOut(0, function () {
        $('#folder_toolbar').html(nav.historyCache[nav.historyCache.length - 1].toolbar);
        $('#tcolumn').html(nav.historyCache[nav.historyCache.length - 1].workspace);
        //historyCache.pop(); журнал -> док1 -> док2 через link - назад => стирается doc1
        $('#folder_toolbar').fadeIn(nav.timeout);
        $('#document_toolbar').fadeIn(nav.timeout);
        // $('#tcolumn').fadeIn(nav.timeout);
      });
    }
    if (nav.historyCache[0] && nav.historyCache[0]['scroll']) {
      // восстанавливаем скролл
      $('#tcolumn').animate({ scrollTop: nav.historyCache[0]['scroll'] }, "slow");
    } else {
      $("#tcolumn").animate({ scrollTop: 0 }, "slow");
    }

    $('.tooltip').hide();
  }
  $(window).trigger('resize');
  this.runCallbacks('loadtable');
};

//МЕТОД ДЛЯ ОБНОВЛЕНИЯ КОЛИЧЕСТВА ЗАПИСЕЙ В ГРУППЕ ЖУРНАЛА
NavigatorDocumentov.prototype.updateNodeTitle = function (node) {
  if (this.showCountGroup && this.groupingTree) {
    nav = this;
    $.ajax({
      url: 'index.php?route=document/folder/get_documents&folder_uid=' + nav.folderUid + '&' + encodeURI(node.key) + encodeURI(nav.filterParams) + '&only_count=true',
      dataType: 'json',
      cache: false,
      success: function (json) {
        var title = node.title.split(' <span');
        node.title = title[0] + ' <span style="font-size:x-small;color:#119909;">' + json.total_documents + '</span>';
        node.tooltip = title[0] + ' (' + json.total_documents + ')';
        node.render(true);
      }
    });
  }

};

NavigatorDocumentov.prototype.changeLimit = function (select) {
  this.limit = select.value;
  this.page = 1;
  this.loadTable(this.tableParams);
};

NavigatorDocumentov.prototype.selectedDocuments = function () {
  let nav = this;
  let checked = $('#selected_all').prop('checked');
  $('input[name*=\'selected\']').prop('checked', checked);
  $('input[name*=\'selected\']').attr('checked', checked);
  $('#selected_all').attr('checked', checked);
  $.each($('input[name*=\'selected\']'), function () {
    nav.selectedDocument($(this));
  });
};

NavigatorDocumentov.prototype.selectedDocument = function (e) {
  if ($(e).prop('checked') == false) {
    $(e).prop('checked', false);
    $(e).removeAttr('checked'); //использование только prop не обеспечивает изменение html чек-бокса, поэтому после открытия-закрытия документа чек-боксы сбрасываются
    $('#selected_all').prop('checked', false);
    $('#selected_all').removeAttr('checked');
  } else {
    $(e).prop('checked', true);
    $(e).attr('checked', 'checked');
  }

  let selected = $('input[name^=\'selected\']:checked');
  let nav = this;
  $.each(nav.buttons, function (button_uid, documents) {
    if (documents.length == 1 && documents[0] == "0") { // кнопка доступна всем и всегда
      $('#folder_button' + button_uid).removeAttr('disabled')
      return
    }
    $('#folder_button' + button_uid).attr('disabled', 'disabled');
    if (documents === undefined) {
      return;
    }
    $.each(selected, function () {
      if ($.inArray(this.value, documents) >= 0) {
        $('#folder_button' + button_uid).removeAttr('disabled');
        ;
        return false;
      }
    });
  });
};

NavigatorDocumentov.prototype.setFieldDraft = function () {
  let nav = this;
  $.each($('#document_form .form-control'), function () {
    if (typeof $(this).attr('name') !== 'undefined') {
      let name = $(this).attr('name').replace(/\[/g, '');
      name = name.replace(/\]/g, '');
      if (typeof $(this).attr('type') !== 'undefined' && $(this).attr('type') == "radio") {
        value = $('input[name=\'' + $(this).attr('name') + '\']:checked').val();
      } else {
        value = $(this).val();
      }
      nav.fieldDraft[name] = value;
    }
  });
},
  NavigatorDocumentov.prototype.updateFieldDraft = function () {
    let nav = this;
    let name, value = '';
    $.each($('#document_form .form-control'), function () {
      if (typeof $(this).attr('name') !== 'undefined') {
        name = $(this).attr('name').replace(/\[/g, '');
        name = name.replace(/\]/g, '');
        if (typeof $(this).attr('type') !== 'undefined' && $(this).attr('type') == "radio") {
          value = $('input[name=\'' + $(this).attr('name') + '\']:checked').val();
        } else {
          value = $(this).val();
        }
        if (nav.fieldDraft[name] != value) {
          save_draft();
          nav.setFieldDraft();
          return;
        }
        ;

      }
    });
  },
  NavigatorDocumentov.prototype.onPage = function (numPage) {
    this.page = numPage;
    this.loadTable(this.tableParams);
  };

NavigatorDocumentov.prototype.sortField = function (fieldUid) {
  if (this.sort == fieldUid) { //переключаетя сортировка
    if (this.order == 'asc') {
      this.order = 'desc';
    } else {
      this.order = 'asc';
    }
  } else {
    this.sort = fieldUid;
    this.order = 'asc';
  }
  this.loadTable(this.tableParams);
};

NavigatorDocumentov.prototype.filterAdd = function () {
  let nav = this;
  nav.numFilter++;
  html = '<tr id="filter-row' + nav.numFilter + '"><td><select name="filter_field[]" onchange="navDocumentov.selectFilterField(' + nav.numFilter + ', this.value);" class="form-control">';
  html += ' <option value="0">' + nav.language.textNone + '</option>';
  $.each(nav.tfields, function () {
    html += ' <option value="' + this.field_uid + '">' + this.tcolumn_name + '</option>';
  });
  html += '</select></td><td><select name="filter_condition[]" class="form-control">';
  $.each(nav.filterConditions, function () {
    html += '<option value="' + this.value + '">' + this.title + '</option>';
  });
  html += '</select></td>' +
    '<td id="filter_value' + nav.numFilter + '"></td>' +
    '<td class="text-right">' +
    '    <button type="button" onclick="$(\'#filter-row' + nav.numFilter + '\').remove();" data-toggle="tooltip" title="' + nav.language.buttonRemove + '" class="btn btn-default"><i class="fa fa-minus-circle"></i></button>' +
    '</td></tr>';
  $('#list_filters tbody').append(html);
};

NavigatorDocumentov.prototype.selectFilterField = function (numFilter, fieldUid) {
  $.ajax({
    url: 'index.php?route=document/folder/get_field_widget&field_uid=' + fieldUid,
    type: 'get',
    dataType: 'json',
    cache: false,
    success: function (json) {
      $('#filter_value' + numFilter).html(json);
    }
  });

};

NavigatorDocumentov.prototype.filter = function () {
  let nav = this;
  nav.filterParams = '';
  $('#list_filters').find('input, textearea, select').each(function () {
    nav.filterParams += '&' + this.name + '=' + $(this).val();
  });
  nav.page = 1; //сбрасываем страницы
  nav.loadTable(nav.tableParams);
  if ($('#list_filters').find('input, textearea, select').length) {
    $('#button_filter').addClass('btn-info');
  } else {
    $('#button_filter').removeClass('btn-info');
  }
  //        nav.folder_toolbar = $('#folder_toolbar').html();    
};

NavigatorDocumentov.prototype.filterReset = function () {
  let nav = this;
  $('#list_filters tbody').html('');
  nav.filterParams = '';
  nav.filterId = 0;
  $('#filter_name').val('');
  nav.loadTable(nav.tableParams);
  $('#button_filter').removeClass('btn-info');
  //        nav.folder_toolbar = $('#folder_toolbar').html();    
};

NavigatorDocumentov.prototype.filterSave = function () {
  let nav = this;
  $.ajax({
    url: 'index.php?route=document/folder/filter_save&folder_uid=' + nav.folderUid + '&filter_name=' + encodeURI($('#filter_name').val()) + '&filter_id=' + nav.filterId,
    type: 'post',
    data: $('#list_filters tbody input[type=\'text\'], #list_filters tbody input[type=\'hidden\'], #list_filters tbody input[type=\'radio\']:checked, #list_filters tbody input[type=\'checkbox\']:checked, #list_filters tbody select, #list_filters tbody textarea'),
    dataType: 'json',
    success: function (json) {
      if (nav.filterId) {
        //обновляем название фильтра
        $('#filter' + nav.filterId).html('<a style="cursor: pointer;" onclick="navDocumentov.filterLoad(' + nav.filterId + ');"><i class="fa fa-minus-circle" onclick="navDocumentov.filterRemove(' + nav.filterId + ');" ></i> ' + $('#filter_name').val() + '</a>');
      } else {
        //добавляем новый фильтр в список
        nav.filterId = json['filter_id'];
        $('#filters').append('<li id="filter' + nav.filterId + '"><a style="cursor: pointer;" onclick="navDocumentov.filterLoad(' + nav.filterId + ');"><i class="fa fa-minus-circle" onclick="navDocumentov.filterRemove(' + nav.filterId + ');" ></i> ' + $('#filter_name').val() + ' </a></li>');

      }
    }
  });
};

NavigatorDocumentov.prototype.filterLoad = function (fid) {
  let nav = this;
  $('#list_filters tbody').html('');
  $.ajax({
    url: 'index.php?route=document/folder/filter_load&filter_id=' + fid,
    type: 'get',
    dataType: 'json',
    success: function (json) {
      //загружаем фильтр в таблицу и применяем его
      nav.numFilter = 0;
      $.each(json['filter'], function (index, filter) {
        nav.numFilter++;
        html = '<tr id="filter-row' + nav.numFilter + '"><td><select name="filter_field[]" onchange="navDocumentov.selectFilterField(' + nav.numFilter + ', this.value);" class="form-control">';
        html += ' <option value="0">' + nav.language.textNone + '</option>';
        $.each(nav.tfields, function () {
          html += ' <option value="' + this.field_uid + '"';
          if (filter['filter_field'] === this.field_uid) {
            html += ' selected ';
          }
          html += '>' + this.name + '</option>';
        });
        html += '</select></td><td><select name="filter_condition[]" class="form-control">';
        $.each(nav.filterConditions, function () {
          html += '<option value="' + this.value + '"';
          if (filter['filter_condition'] === this.value) {
            html += ' selected="selected"';
          }
          html += '>' + this.title + '</option>';
        });
        html += '</select></td>' +
          '<td id="filter_value' + nav.numFilter + '">' + filter['filter_form'] + '</td>' +
          '<td class="text-right">' +
          '   <button type="button" onclick="$(\'#filter-row' + nav.numFilter + '\').remove();" data-toggle="tooltip" title="' + nav.language.buttonRemove + ' class="btn btn-default"><i class="fa fa-minus-circle"></i></button>' +
          '</td></tr>';
        $('#list_filters tbody').append(html);
      });
      nav.filterId = fid;
      $('#filter_name').val(json['filter_name']);
    }
  });
};

NavigatorDocumentov.prototype.filterRemove = function (fid) {
  $.ajax({
    url: 'index.php?route=document/folder/filter_remove&filter_id=' + fid,
    type: 'get',
    dataType: 'json',
    success: function (json) {
      $('#filter' + fid).remove();
    }
  });
  return false;
};

NavigatorDocumentov.prototype.keySearch = function (key) {
  if (key == 13 || key == 'Enter') {
    //нажат Enter
    this.search();
  }
};

NavigatorDocumentov.prototype.search = function () {
  let nav = this;
  $('.alert-warning').remove();
  if ($('#block-search').is(':visible')) {
    if (nav.inputSearch != $('input[name=\'inputSearch\']').val() || (nav.prevSearchType != nav.searchType && nav.prevSearchType != "")) {
      let search_string = $('input[name=\'inputSearch\']').val();
      if (this._validate_search_string(search_string)) {
        nav.inputSearch = search_string;
        $('input[name=\'inputSearch\']').attr('value', nav.inputSearch);//отображаем в value, чтобы записать поисковую строку кэш и она не была пустой при открытии дока и возврата по кнопке назад
        nav.prevSearchType = nav.searchType;
        $('.alert-warning').remove();
        nav.page = 1; //сбрасываем страницы
        nav.loadTable(nav.tableParams); //nav.tableParams == 'undefined'
      } else {
        //alert("в режиме полнотекстового поиска запрос должен содержать слова длиною не менее 4-х символов");
        $('input[name=\'inputSearch\']').blur();
        $('#folder_toolbar').append('<div class="alert alert-warning">' + nav.ftsearch_invalid_input_msg + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        return;
      }
    }
    if ($('input[name=\'inputSearch\']').val() !== '') {
      $('#button_search').addClass('btn-info');
    } else {
      $('#button_search').removeClass('btn-info');
    }
    $('#block-search').fadeOut();
  } else {
    $('#block-search').fadeIn();
    $('input[name=\'inputSearch\']').focus();
  }
};

NavigatorDocumentov.prototype.keySearch = function (key) {
  if (key == 13 || key == 'Enter') {
    //нажат Enter
    this.search();
  }
};

NavigatorDocumentov.prototype.setSearchType = function (search_type) {
  this.searchType = search_type;
  if (search_type === 'quick') {
    $('#block-search span button i').removeClass("fa-book");
    $('#block-search span button i').addClass("fa-rocket");
  } else if (search_type === 'fulltext') {
    $('#block-search span button i').removeClass("fa-rocket");
    $('#block-search span button i').addClass("fa-book");
  } else {
    $('#block-search span button i').removeClass("fa-rocket");
    $('#block-search span button i').removeClass("fa-book");
  }
};

NavigatorDocumentov.prototype._validate_search_string = function (search_string) {
  if (search_string && this.searchType === 'fulltext') {
    let quoter_reg = /^\".+\"$/;
    if (search_string.match(quoter_reg)) {
      search_string = search_string.replace(/^\"/, "");
      search_string = search_string.replace(/\"$/, "");
    } else {
      var tokens = search_string.split(" ");
      tokens = tokens.filter(function (token) {
        return (token.length >= 4);
      });
      search_string = tokens.join(" ");
    }
    return (search_string.length >= 4);

  } else
    return true;
};

NavigatorDocumentov.prototype.registerCallback = function (event_names_str, callback) {
  if (event_names_str) {
    var event_names = event_names_str.split(', ');
    for (var i = 0; i < event_names.length; i++) {
      var event_name = event_names[i];
      if (!this.callbacks[event_name]) {
        this.callbacks[event_name] = [];
        this.callbacks[event_name].push(callback);
      } else {
        this.callbacks[event_name].push(callback);
      }
    }
  }
};

NavigatorDocumentov.prototype.runCallbacks = function (event_name) {
  if (event_name) {
    if (this.callbacks[event_name]) {
      for (var i = 0; i < this.callbacks[event_name].length; i++) {
        this.callbacks[event_name][i]();
      }
    }
  }
};



