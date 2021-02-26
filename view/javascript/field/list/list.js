function FieldList(data) {
  this.data = JSON.parse(data);
  const prefix = this.data.MODULE_NAME + '-';
  this.idTableValues = prefix + 'table_values';
  this.idButtonAddValue = prefix + 'button_add_value';
  this.idBlockSourceField = prefix + 'block_source_field';
  this.idBlockTableValues = prefix + 'block_table_values';
  this.idSelectMulti = prefix + 'select_multi';
  this.idSelectVisual = prefix + 'select_visualization';
  this.idBlockEmtyValue = prefix + 'list_null_' + this.data.unique;
  this.classValueValue = prefix + 'value_value';
  this.classValueTitle = prefix + 'value_title';
}

FieldList.prototype.selectType = function () {
  let list = this;
  if ($('select[name=\'field\[source_type\]\']').val() == "table") {
    $('#' + list.idBlockSourceField).hide();
    $('#' + list.idBlockTableValues).show();
    $('#' + list.idButtonAddValue).show();
  } else {
    $('#' + list.idBlockSourceField).show();
    $('#' + list.idBlockTableValues).hide();
    $('#' + list.idButtonAddValue).hide();
  }
};

FieldList.prototype.getAdminForm = function () {
  let data = this.data;
  let list = this;

  list.selectType();
  $('select[name=\'field\[source_type\]\']').on('change', function () {
    list.selectType();
  });

  //------- ДОБАВЛЕНИЕ ВАРИАНТА СПИСКА В ТАБЛИЦУ ------- //
  $('#' + list.idButtonAddValue).on('click', function () {
    //получаем максимальное значение из всех вариантов
    let $values = $('#' + list.idTableValues + ' .' + list.classValueValue);
    let val = 0;
    $.each($values, function () {
      if (val < parseInt($(this).val())) {
        val = parseInt($(this).val());
      }
    });
    val++;
    let idValue = val; //значение администратор может изменить, поэтому для ИД нужно проверить - нет ли уже  таких ИД
    while ($('input[name="field[values][' + idValue + '][value]"]').length) {
      idValue++;
    }
    $tr = $('<tr>');
    $('<td>').append(
      $('<input>').attr('type', 'text').attr('name', 'field[values][' + idValue + '][value]').addClass('form-control ' + list.classValueValue).val(val)
    ).appendTo($tr);
    $('<td>').append(
      $('<input>').attr('type', 'text').attr('name', 'field[values][' + idValue + '][title]').addClass('form-control ' + list.classValueTitle)
    ).appendTo($tr);
    $divSelected = $('<div>');
    let typeSelected = 'radio';
    let nameSelected = 'field[default_value]';
    if ($('#' + list.idSelectMulti).val() == '1') {
      typeSelected = 'checkbox';
      nameSelected += '[]';
    }
    $divSelected.addClass(typeSelected + ' text-center');
    $('<label>').append(
      $('<input>').attr('type', typeSelected).attr('name', nameSelected).val(idValue)
    ).appendTo($divSelected);
    $('<td>').append($divSelected).appendTo($tr);
    $tdButton = $('<td>');
    $('<span>').addClass('btn btn-default').attr('data-toggle', 'tooltip').attr('title', data.text.button_move).append('<i>').addClass('fa fa-hand-pointer-o').appendTo($tdButton);
    $('<button>').attr('type', 'button').addClass('btn btn-default').attr('data-toggle', 'tooltip').attr('title', data.text.button_remove).append('<i>').addClass('fa fa-remove').appendTo($tdButton).on('click', function () {
      $(this).parent().parent().remove();
    });
    $tdButton.appendTo($tr);
    $('#' + list.idTableValues + ' tbody').append($tr);
  });

  // ------ ИНИЦИАЛИЗИРУЕМ ВВЕДЕННЫЕ РАНЕЕ ВАРИАНТЫ ------- //
  if (data.params && data.params.values) {
    $.each(data.params.values, function (id, value) {
      $('#' + list.idButtonAddValue).trigger('click');
      $tr = $('#' + list.idTableValues + ' tr:last');
      $('.' + list.classValueValue, $tr).val(value.value);
      $('.' + list.classValueTitle, $tr).val(value.title);
      if (value.checked) {
        //значение по умолчанию
        $tr.find('input[type=radio]').attr('checked', 'checked');
        $tr.find('input[type=checkbox]').attr('checked', 'checked');
      }
    });
  }
  // сортировка
  $('#' + list.idTableValues + ' tbody').sortable({ cursor: 'move' });


  $('#' + list.idSelectMulti).on('change', function () {
    if ($(this).val() == '1') {
      var type = "checkbox";
      var search = "radio";
      var name = "field[default_value][]";
      //отключаем список при множественном выборе
      if ($('#' + list.idSelectVisual).val() == 2) {
        $('#' + list.idSelectVisual).val(1);
      }
      $('#' + list.idSelectVisual + ' option[value=2]').attr('disabled', 'disabled');
    } else {
      var type = "radio";
      var search = "checkbox";
      var name = "field[default_value]";
      $('#' + list.idSelectVisual + ' option[value=2]').removeAttr('disabled');
    }
    $('#' + list.idTableValues + ' :' + search).each(function () {
      $(this).prop('type', type);
      $(this).prop('name', name);
      $(this).parent().parent().removeClass(search);
      $(this).parent().parent().addClass(type);
    });
  });
  $('#' + list.idSelectMulti).trigger('change');
};

FieldList.prototype.getWidgetForm = function () {
  let list = this;
  let data = this.data;
  if (data.visualization != 2) {
    var $selected = $('input[name*="' + data.NAME + '"]:checked');
    if ($selected.length > 0) {
      $('#' + list.idBlockEmtyValue).html('');
    } else {
      $('#' + list.idBlockEmtyValue).html('<input name="' + data.NAME + '" value="null" type="hidden">');
    }
    function saveChecked() {
      var $selected = $('input[name*="' + data.NAME + '"]:checked');
      var values = [];
      if ($selected.length > 0) {
        $('#' + list.idBlockEmtyValue).html('');
      } else {
        $('#' + list.idBlockEmtyValue).html('<input name="' + data.NAME + '" value="null" type="hidden">');
      }
      $.each($selected, function () {
        values.push($(this).val());
      });
      $('.' + data.ID).val(values.join(','));
      $('.' + data.ID).trigger('change');
      if (typeof save_draft == 'function') {
        save_draft();
      }
    }
    $('input[name*="' + data.NAME + '"]').on('click', function () {
      saveChecked();
      if (data.visualization !== "0") {
        if (this.type === 'radio') {
          $('#table-list_' + data.unique + ' tr').css('background-color', '');
          $(this).parent().parent().parent().css('background-color', '#eee');
        } else {
          if (this.checked) {
            $(this).parent().parent().parent().css('background-color', '#eee');
          } else {
            $(this).parent().parent().parent().css('background-color', '');
          }
        }
      }
    });
    saveChecked();
  } else {
    //выпадающий список
    $('select[name="' + data.NAME + '"]').on('change', function () {
      $('.' + data.ID).val($(this).val());
      $('.' + data.ID).trigger('change');
    }).trigger('change');

  }


};