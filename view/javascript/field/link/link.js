function FieldLink(data) {
  this.data = JSON.parse(data);
  this.prefix = this.data.MODULE_NAME + '-';

}

FieldLink.prototype.getAdminForm = function () {
  if (!$.fn.fieldSelectorGetter) {
    addScript('view/javascript/jquery/fieldselector/fieldselectorgetter.js');
  }
  const link = this,
    $doctypeUid = $('#' + link.prefix + 'doctype_uid'),
    $doctypeName = $('#' + link.prefix + 'doctype_name'),
    $doctypeFieldName = $('#' + link.prefix + 'doctype_field_name'),
    $doctypeFieldUid = $('#' + link.prefix + 'doctype_field_uid'),
    $multiSelect = $('#' + link.prefix + 'multi_select'),
    $selectList = $('#' + link.prefix + 'select_list'),
    $delimiter = $('#' + link.prefix + 'delimiter'),
    $sourceType = $('#' + link.prefix + 'source_type'),
    $sourceTypeField = $('#' + link.prefix + 'source_type_field'),
    // $addCondition = $('#' + link.prefix + 'button_add_condition');
    idForm = '#' + link.prefix + 'form',
    idTableCondition = '#' + link.prefix + 'table_condition',
    idTemplateConditionRow = '#' + link.prefix + 'template_condition_row',
    disableForm = function () {
      if (!$doctypeUid.val()) {
        $doctypeFieldName.val('');
        $doctypeName.val(link.data.text.text_none_type);
        $(`${idForm} input,${idForm} select,${idForm} button`).attr('disabled', 'disabled');
        $doctypeUid.removeAttr('disabled');
        $doctypeName.removeAttr('disabled');
        $multiSelect.removeAttr('disabled');
      }
    };

  if ($selectList.val() == 1) {
    $multiSelect.val('0');
    $multiSelect.attr('disabled', 'disabled');
  }


  $selectList.on('change', function () {
    if (this.value == '1') {
      $multiSelect.val('0');
      $multiSelect.attr('disabled', 'disabled');
    } else {
      $multiSelect.removeAttr('disabled');
    }
  });

  $multiSelect.on('change', function () {
    if (this.value == '0') {
      $delimiter.attr('disabled', 'disabled');
    } else {
      $delimiter.removeAttr('disabled');
    }
  });

  $multiSelect.trigger('change');
  disableForm();

  // источник данных - все документы доктайпа или документы из поля
  if ($sourceType.val() == 'field') {
    $sourceTypeField.show();
  } else {
    $sourceTypeField.hide();
  }
  $sourceType.on('change', function () {
    if (this.value == 'doctype') {
      $sourceTypeField.hide();
    } else {
      $sourceTypeField.show();
    }
  });

  $doctypeName.on('click', function () {
    if ($(this).val() == link.data.text.text_none_type) {
      $(this).val('');
    }
  });

  $doctypeName.on('blur', function () {
    if ($(this).val() == '') {
      $(this).val(link.data.text.text_none_type);
    }
  });

  $doctypeName.autocomplete({
    'source': function (request, response) {
      if (request == link.data.text.text_none_type) {
        request = '';
      }
      $.ajax({
        url: 'index.php?route=doctype/doctype/autocomplete&filter_name=' + encodeURIComponent(request),
        dataType: 'json',
        success: function (json) {
          json.unshift({
            doctype_uid: 0,
            name: link.data.text.text_none_type
          });
          response($.map(json, function (item) {
            return {
              label: item['name'],
              value: item['doctype_uid']
            };
          }));
        }
      });
    },
    'select': function (item) {
      if (item['value']) {
        $doctypeName.val(item['label']);
        $doctypeUid.val(item['value']);
        $(`${idForm} input,${idForm} select,${idForm} button`).removeAttr('disabled');
        $multiSelect.trigger('change');
      } else {
        $doctypeName.val("");
        $doctypeUid.val("");
        disableForm();
      }
    }
  });

  $doctypeFieldName.autocomplete({
    'source': function (request, response) {
      $.ajax({
        url: 'index.php?route=doctype/doctype/autocomplete_field&filter_name=' + encodeURIComponent(request) + '&doctype_uid=' + $doctypeUid.val() + '&access_view=0',
        dataType: 'json',
        success: function (json) {
          json.unshift({
            field_uid: 0,
            name: link.data.text.text_none
          });
          response($.map(json, function (item) {
            if (item['field_uid'] !== '{{field_uid}}') {
              return {
                label: item['name'],
                value: item['field_uid']
              };
            }
          }));
        }
      });
    },
    'select': function (item) {
      if (item['value']) {
        $doctypeFieldName.val(item['label']);
        $doctypeFieldUid.val(item['value']);
        if ($('#input-field_name').val() === '') {
          $('#input-field_name').val(item['label']);
        }
      } else {
        $doctypeFieldName.val("");
        $doctypeFieldUid.val("0");
      }
    }
  });

  let rowCondition = 0;
  let props = {};
  $(idTableCondition + ' th button').on('click', function () {
    $(idTableCondition + ' tbody').append(getTemplateContent(idTemplateConditionRow));
    let $tr = $(idTableCondition + ' tbody tr:last');

    rowCondition++;

    $selectorCondition = $tr.find('select:first');
    if (rowCondition == 1) {
      $selectorCondition.remove();
    } else {
      $selectorCondition.attr('name', 'field[conditions][' + rowCondition + '][concat]').val(props.concat);
    }
    $tr.find('select:last').attr('name', 'field[conditions][' + rowCondition + '][comparison]').val(props.comparison);
    $tr.find('input[type=hidden]:first').attr('name', 'field[conditions][' + rowCondition + '][field_1_uid]').val(props.field_1_uid || '');
    $tr.find('input[type=text]:first').val(props.field_1_name || '').autocomplete(Documentov.getAutocompleteField($doctypeUid.val(), 0));
    $tr.find('input[type=text]:last').fieldSelectorGetter({
      name: rowCondition,
      prefixName: 'field[conditions][',
      postfixName: '][field_2_uid]',
      attributeName: 'id',
      doctypeUid: link.data.doctype_uid,
      fieldUid: props.field_2_uid || '',
      fieldName: props.field_2_name || '',
      fieldSetting: props.field_2_setting || '0',
      method: props.method_name || '',
      onlyStandardGetterParam: true
    });
    props = {};
  });
  if (link.data.params && link.data.params.conditions) {
    link.data.params.conditions.forEach(function (condition) {
      props = condition;
      $(idTableCondition + ' th button').trigger('click');
    });
  }

};

FieldLink.prototype.getWidgetForm = function () {
  const link = this.data,
    name = link.NAME,
    id = link.ID,
    unique = link.unique,
    fieldUid = link.field_uid,
    documentUid = link.document_uid,
    doctypeUid = link.doctype_uid,
    doctypeFieldUid = link.doctype_field_uid,
    sourceFieldUid = link.source_field_uid,
    filters = link.filters,
    list = link.list,
    multiSelect = link.multi_select,
    filterForm = link.filter_form || '',
    nameEscaping = name.replace(/([\[,\]])/g, '\\$1'),
    $field = $('[name=' + nameEscaping + ']'),
    $fieldBlock = $('#' + link.MODULE_NAME + '-block-' + fieldUid),
    $input = $('input[name=' + link.MODULE_NAME + '-' + nameEscaping + unique + ']'),
    $switcher = $('input[name=' + link.MODULE_NAME + '-name-' + unique + ']');
  let notUpdate = false;
  // ФУНКЦИЯ ДЛЯ ЗАПИСИ ВЫБРАННЫХ ЗНАЧЕНИИ В ХИДДЕН {{ NAME }}
  const formData = function () {
    if (notUpdate) {
      notUpdate = false;
      return;
    }
    let values = [];
    $.each($fieldBlock.find('input'), function () {
      let type = $(this).attr('type');
      if (type == 'checkbox' || type == 'radio') {
        if ($(this).is(':checked')) {
          values.push($(this).val());
        }
      } else {
        values.push($(this).val());
      }
    });
    $field.val(values.join(','));
    notUpdate = true;
    $field.trigger('change');
  };
  if (list == '2') {
    // СПИСОК С ПЕРЕКЛЮЧАТЕЛЯМИ
    $switcher.on('change', function () {
      formData();
      $.each($switcher, function () {
        let $td = $(this).parent().parent().parent();
        if ($(this).is(':checked')) {
          $td.addClass('background2');
        } else {
          $td.removeClass('background2');
        }
      });
    });
    // ОБРАБОТЧИК СКРЫТОГО ПОЛЯ СО ЗНАЧЕНИЕМ
    $field.on('change', function () {
      // значение могло быть изменено через УШ, может содержать несколько значений через запятую
      let $this = $(this);
      let value = $this.val(),
        $sw;
      $switcher.prop('checked', false);
      $.each($switcher, function () {
        if ($(this).attr('type') == 'radio') {
          if ($(this).val() == value) {
            $(this).prop('checked', true);
            return;
          }
        } else {
          if (value.indexOf($(this).val()) > -1) {
            $(this).prop('checked', true);
          } else {
            $(this).prop('checked', false);
          }
        }
        $sw = $(this);
      });
      notUpdate = true;
      if ($sw) {
        $sw.trigger('change');
      }

    });
    return;
  }
  // АВТОКОМПЛИТ
  $input.autocomplete({
    'source': function (request, response) {
      let url = 'index.php?route=document/document/autocomplete&doctype_uid=' + doctypeUid + '&field_uid=' + doctypeFieldUid;
      if (sourceFieldUid) {
        url += '&source_field_uid=' + sourceFieldUid;
      }
      if (documentUid) {
        url += '&document_uid=' + documentUid;
      }
      if (request) {
        url += '&filter_name=' + encodeURIComponent(request);
      }
      if (filters) {
        url += filters;
      }
      $.ajax({
        url: url,
        dataType: 'json',
        cache: false,
        success: function (json) {
          if (multiSelect == '1') {
            // если множественный выбор, убираем из списка уже выбранные элементы
            selectedValues = $('#' + id).val().split(',');
            json = json.filter((item) => selectedValues.indexOf(item['document_uid']) < 0);
          }

          json.unshift({ document_uid: 0, name: link.text.text_none });
          response($.map(json, function (item) { // если пользователь не выберет значение из списка, а введет его руками
            if (json.length == 2 && item['document_uid'] > 0) {
              $('input[name=' + name + ']').val(item['document_uid']);
            }
            return { label: item['name'], value: item['document_uid'] };
          }));

        }
      });
    },
    'select': function (item) {
      if (multiSelect == '1' && !filterForm) {
        // МНОЖЕСТВЕННЫЙ ВЫБОР
        if (item['value']) {
          $('#block_' + id + item['value']).remove();
          $fieldBlock.append('<div id="block_' + id + item['value'] + '" class="pointer" ><i class="fa fa-minus-circle"></i> <i class="fa fa-hand-pointer-o"></i> ' + item['label'] + '<input type="hidden" value="' + item['value'] + '"/></div>');
          $input.val('');
          let val = $field.val(),
            vals = [];
          if (val.length >= 36) {
            vals = $field.val().split(',');
          }
          let pos = vals.indexOf(item['value']);
          if (pos >= 0) {
            vals.splice(pos, 1);
          }
          vals.push(item['value']);
          $field.val(vals.join(','));
        }
      } else {
        // ОДИНОЧНЫЙ ВЫБОР
        if (item['value']) {
          $('#' + id).val(item['value']);
          $input.val(item['label']);
        } else {
          $('#' + id).val("");
          $input.val("");
        }
        $('.' + id).val($('#' + id).val());
      }
      notUpdate = true;
      $input.trigger('change');
      $('.' + id).trigger('change');
      if (typeof save_draft == 'function') {
        save_draft();
      }
    }
  });
  //
  if (multiSelect == '0') {
    // ОДИНОЧНОЕ ПОЛЕ
    // ОБРАБОТЧИК СКРЫТОГО ПОЛЯ СО ЗНАЧЕНИЕМ ДЛЯ ОДИНОЧНОЙ ССЫЛКИ + СПИСОК
    $field.on('change', function () {
      // поле может быть изменено через УШ и запущен триггер изменения
      if (notUpdate) {
        notUpdate = false;
        return;
      }
      if (!$(this).val()) {
        $input.val('');
      } else {
        $.ajax({
          url: 'index.php?route=document/document/autocomplete&field_uid=' + doctypeFieldUid + '&document_uid=' + $(this).val(),
          dataType: 'json',
          cache: false,
          success: function (json) {
            if (json && json.length) {
              $input.val(json[0]['name']);
              if (typeof save_draft == 'function') {
                save_draft();
              }
            }
          }
        });
      }
      // 
    });
  } else if (!filterForm) {
    // МНОЖЕСТВЕННОЕ ПОЛЕ
    let valueId = link.value ? link.value.id : '';
    // СОРТИРОВКА ВО МНОЖЕСТВЕННОМ ПОЛЕ
    $fieldBlock.sortable({ cursor: 'move', stop: formData });
    // ИЗМЕНЕНИЕ ЗНАЧЕНИЯ (МН ВЫБОР)
    $field.on('change', function () {
      // поле может быть изменено через УШ и запущен триггер изменения
      if (notUpdate) {
        notUpdate = false;
        return;
      }
      $fieldBlock.children().remove();
      if ($(this).val()) {
        $.ajax({
          url: 'index.php?route=document/document/autocomplete&field_uid=' + doctypeFieldUid + '&document_uid=' + $(this).val(),
          dataType: 'json',
          cache: false,
          success: function (json) {
            $fieldBlock.children().remove();
            if (json && json.length) {
              json.forEach(function (item) {
                $fieldBlock.append('<div id="block_' + id + item['document_uid'] + '" class="pointer" ><i class="fa fa-minus-circle"></i> <i class="fa fa-hand-pointer-o"></i> ' + item['name'] + '<input type="hidden" value="' + item['document_uid'] + '"/></div>');
              });
              $input.val('');
              if (typeof save_draft == 'function') {
                save_draft();
              }
              notUpdate = true;
              formData();
            }
          }
        });
      }
      // 
    });
    // МНОЖ ССЫЛКА - УДАЛЕНИЕ ОДНОГО ЗНАЧЕНИЯ
    $fieldBlock.delegate('.fa-minus-circle', 'click', function () {
      $(this).parent().remove();
      if (typeof save_draft == 'function') {
        save_draft();
      }
      formData();
    });
    // formData();
  }
};

FieldLink.prototype.getWidgetView = function () {
};
