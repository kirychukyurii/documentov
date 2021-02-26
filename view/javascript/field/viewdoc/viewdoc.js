function FieldViewdoc(data) {
  this.data = JSON.parse(data);
  const prefix = this.data.MODULE_NAME + '-';
  // админ форма
  this.idAccordionTemplates = '#' + prefix + 'accordion_templates';
  this.classBtnAddAccordionRow = '.' + prefix + 'btn-add';
  this.classBtnTemplate = '.' + prefix + 'button_template';
  this.classBlockDoctype = '.' + prefix + 'divdt';
  this.classDoctype = '.' + prefix + 'divdt_value';
  this.classTemplateManage = '.' + prefix + 'block_manage_template';
  this.classTemplateContent = '.' + prefix + 'block_content_template';
  this.idTemplateAccordionRow = '#' + prefix + 'template_accordion_row';
  this.idAccordionCollapse = '#' + prefix + 'collapse';
  this.idTemplateWindowTemplate = '#' + prefix + 'window_template';
  this.idWindowTemplate = '#' + prefix + 'window';
  this.idWindowButton = '#' + prefix + 'window_save';
  this.numRow = 0;
}

FieldViewdoc.prototype.getAdminForm = function () {
  const viewdoc = this;
  //отключаем версионность
  $('#input-history').attr('disabled', 'disabled');
  $('#input-history').removeAttr('checked');
  //отключаем обязательность
  $('#input-required').val('');
  $('#input-required').attr('disabled', 'disabled');
  $('#input-required').removeAttr('checked');
  //отключаем уникальность
  $('#input-unique').val('');
  $('#input-unique').attr('disabled', 'disabled');
  $('#input-unique').removeAttr('checked');
  //отключаем полнотекстовый поиск
  $('#input-ft_index').val('');
  $('#input-ft_index').attr('disabled', 'disabled');
  $('#input-ft_index').removeAttr('checked');
  // добавление шаблона
  const $btnAddRow = $(viewdoc.classBtnAddAccordionRow);
  $btnAddRow.on('click', function () {
    viewdoc.addAccordionRow();
    // console.log($(viewdoc.idAccordionTemplates + ' > div:last').children(':first').find('[data-parent]'));
    // $(viewdoc.idAccordionTemplates + ' > div:last').children(':first').find('[data-parent]').collapse();
  });
  if (viewdoc.data.params.templates) {
    viewdoc.data.params.templates.forEach((templ) => { viewdoc.addAccordionRow(templ.doctype_uid, templ.doctype_name, templ.template); });
  }
};

FieldViewdoc.prototype.addAccordionRow = function (doctypeUid = '', doctypeName = '', template = '') {
  const viewdoc = this;
  viewdoc.numRow++;
  $(viewdoc.idAccordionTemplates).append(getTemplateContent(viewdoc.idTemplateAccordionRow));
  const $addedRow = $(viewdoc.idAccordionTemplates + ' > div:last'); //добавленная строка аккордеона
  let $headerLink = $addedRow.children(':first').find('[data-parent]');
  $headerLink.attr('href', viewdoc.idAccordionCollapse + this.numRow);
  let $contentPanel = $addedRow.children(':last');
  $contentPanel.attr('id', viewdoc.idAccordionCollapse.slice(1) + this.numRow);

  // автокомплит на выбор доктайпа
  let autocompleteDoctype = Documentov.getAutocompleteDoctype();
  autocompleteDoctype.source = function (request, response) {
    $.ajax({
      url: 'index.php?route=doctype/doctype/autocomplete&filter_name=' + encodeURIComponent(request),
      dataType: 'json',
      cache: false,
      success: function (json) {
        let $doctypeUids = $(viewdoc.idAccordionTemplates + ' [name*=doctype_uid]');
        let doctypeUids = [];
        $.each($doctypeUids, function () {
          let val = $(this).prev().val();
          if (val) {
            doctypeUids.push(val);
          }
        });
        json.unshift({ doctype_uid: 0, name: Documentov.text.text_none });
        response($.map(json, function (item) {
          if (doctypeUids.indexOf(item['doctype_uid']) < 0) {
            return { label: item['name'], value: item['doctype_uid'] };
          }
        }));
      }
    });
  };
  $contentPanel.find(viewdoc.classDoctype + ' input[type=hidden]')
    .attr('name', 'field[templates][' + viewdoc.numRow + '][doctype_uid]')
    .val(doctypeUid);
  $contentPanel.find(viewdoc.classDoctype + ' input[type=text]')
    .val(doctypeName)
    .autocomplete(autocompleteDoctype)
    .on('change', function () {
      let val = $(this).val();
      if (!val) {
        val = viewdoc.data.text.text_new_doctype;
      }
      $headerLink.html(val);
    });
  if (doctypeName) {
    $headerLink.html(doctypeName);
  } else {
    $(viewdoc.idAccordionCollapse + this.numRow).collapse();
  }
  // скрытое поле с шаблоном  
  let $template = $contentPanel.find(viewdoc.classTemplateManage + ' [type=hidden]');
  $template.attr('name', 'field[templates][' + viewdoc.numRow + '][template]');
  if (template) {
    $template.val(template);
    $contentPanel.find(viewdoc.classTemplateContent).html(template);
  }
  // КЛИК ПО КАРАНДАШУ - РЕДАКТИРОВАНИЕ ШАБЛОНА
  $contentPanel.find(viewdoc.classBtnTemplate).on('click', function () {
    let doctypeUid = $contentPanel.find(viewdoc.classDoctype + ' input[type=hidden]').val();
    if (!doctypeUid) {
      alert(viewdoc.data.text.text_select_template_doctype);
      return;
    }

    let $content = $(getTemplateContent(viewdoc.idTemplateWindowTemplate));
    // показываем окно шаблона
    Documentov.showModalWindow(viewdoc.idWindowTemplate.slice(1), viewdoc.data.text.text_template, $content, viewdoc.idWindowButton.slice(1), 'lg', undefined, undefined, undefined, { show: true, backdrop: false });
    let $editor = $(viewdoc.idWindowTemplate + ' textarea');
    // загружаем шаблон
    if ($template.val()) {
      $editor.val($template.val());
    }
    // инициализируем редактор
    init_summernote_s($editor, doctypeUid, { hideConditions: true });
    $(viewdoc.idWindowTemplate).on({
      "shown.bs.dropdown": function () {
        $('.input-select_field').focus(); // фокус на поле автокомплита редактора
      }
    });

    // событие сохранение шаблона в окне
    $(viewdoc.idWindowButton).on('click', function () {
      let content = $editor.val();
      $template.val(content);
      if (!content) {
        content = viewdoc.data.text.text_empty_template;
      } else {
        $addedRow.find(viewdoc.classBlockDoctype).hide(); //шаблон есть, скрываем выбор доктайпа
      }
      $contentPanel.find(viewdoc.classTemplateContent).html(content);
      $(viewdoc.idWindowTemplate).remove();
    });
  });

};

FieldViewdoc.prototype.getWidgetView = function () {
};

FieldViewdoc.prototype.getWidgetForm = function () {
};
