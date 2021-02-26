function FieldText(data) {
  this.data = JSON.parse(data);
  let prefix = this.data.MODULE_NAME + '-'; //'action_report-';
  this.idEditorEnabled = prefix + 'select_editor_enabled';
  this.idTabImage = prefix + 'tab_editor_image';
  this.idBlockScript = prefix + 'block_select_script_enabled';
  this.idModalWindow = prefix + 'modal_window';
  // this.idBtnUploadImage = prefix + 'button-image';
  this.idBtnInsertImage = prefix + 'button_insert_image';
  this.idFileUpQueue = prefix + 'file_queue';
  this.idFileUpInput = prefix + 'file_input';
  this.$divField = $('#' + this.data.MODULE_NAME + '-ID');
  this.$divField.attr('id', this.data.ID);
}

FieldText.prototype.getAdminForm = function () {
  let text = this;

  if ($('#' + text.idEditorEnabled).val() == "true") {
    $('#' + text.idTabImage).show();
    $('#' + text.idBlockScript).show();
  } else {
    $('#' + text.idTabImage).hide();
    $('#' + text.idBlockScript).hide();
  }

  $('#' + text.idEditorEnabled).on('change', function () {
    if ($(this).val() == "true") {
      $('#' + text.idTabImage).show();
      $('#' + text.idBlockScript).show();
    } else {
      $('#' + text.idTabImage).hide();
      $('#' + text.idBlockScript).hide();
    }
  });
};

FieldText.prototype.getWidgetForm = function () {
  let data = this.data;
  let text = this;
  $('#' + data.ID).val(data.value);
  if (data.filter_form) {
    return; //компактная фильтр-форма без редактора
  }

  if (typeof text_clear_field === "undefined") {
    addScript("view/javascript/field/text/text_validation.js");
  }
  if (data.editor_enabled) {
    if (typeof field_functions !== "undefined") {
      field_functions.push(function () {
        text_clear_field('data.ID');
      }); // <p><br></p> - если поле содержит эту последовательность, оно пустое
    }
    loadSummernote();
    let languageCode = data.text.code.split('-')[0];
    $.summernote.lang[languageCode] = data.text.text_editor;
    let summernoteParams = {
      lang: languageCode,
      disableDragAndDrop: true,
      emptyPara: '',
      height: document.documentElement.clientHeight / 2,
      fontsize: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '30', '36', '48', '64'],
      callbacks: {
        onBlur: function () {
          $('textarea[name=\'' + data.NAME + '\']').trigger('change');
        }
      }
    };
    if (data.field_uid) { //при наличии field_uid добавляем загрузку изображений
      summernoteParams.toolbar = [
        ['style', ['style']], ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
        ['fontsize', ['fontsize']],
        ['fontname', ['fontname']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'image']],
        ['view', ['fullscreen', 'codeview']]
      ];
      summernoteParams.buttons = {
        image: function (context) {
          var ui = $.summernote.ui;
          // create button
          var button = ui.button({
            contents: '<i class="note-icon-picture" />',
            tooltip: $.summernote.lang[languageCode].image.image,
            click: function () {
              context.invoke('saveRange');
              $('#' + text.idModalWindow).remove();
              $.ajax({
                url: 'index.php?route=field/text&field_uid=' + data.field_uid + '&document_uid=' + data.document_uid,
                dataType: 'html',
                beforeSend: function () {
                },
                success: function (html) {
                  $('body').append('<div id="' + text.idModalWindow + '" class="modal">' + html + '</div>');
                  $('#' + text.idModalWindow).modal('show');
                  $('#' + text.idModalWindow).draggable({ handle: '.modal-header' });
                  $('#' + text.idModalWindow).delegate('#' + text.idBtnInsertImage, 'click', function (e) {
                    e.preventDefault();
                    context.invoke('restoreRange');
                    context.invoke("editor.insertImage", $(this).data('href'), function ($image) {
                      $image.css('max-width', '100%');
                    });
                    $('#' + text.idModalWindow).modal('hide');
                  });
                }
              });
            }
          });
          return button.render();
        }
      };
    } else {
      summernoteParams.toolbar = [
        ['style', ['style']], ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
        ['fontsize', ['fontsize']],
        ['fontname', ['fontname']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link']],
        ['view', ['fullscreen', 'codeview']]
      ];
    }
    $('#' + data.ID)
      .on('change', function (e, param) {
        if (param == 'templcond') {
          $(this).parent().find('.note-editable').html($(this).val()); // если выполняется запись в текстарию, например, через УШ
        }
      })
      .summernote(summernoteParams);
  }

};

FieldText.prototype.getImageWindow = function () {
  let text = this;
  if (!$.fileup) {
    addScript('view/javascript/jquery/fileup/src/fileup.js');
    addStyle('view/javascript/jquery/fileup/src/fileup.css');
  }
  $.fileup({
    i18n: text.data.text.text_file_upload,
    url: 'index.php?route=field/text/upload&field_uid=' + text.data.field_uid + '&document_uid=' + text.data.document_uid,
    lang: 'en',
    inputID: text.idFileUpInput,
    queueID: text.idFileUpQueue,
    filesLimit: 1,
    onSuccess: function (response, file_number, file) {
      file_uid = JSON.parse(response);
      $('#' + text.idBtnInsertImage).data('href', '/index.php?route=field/text/file&field_uid=' + text.data.field_uid + '&file_uid=' + file_uid['success'] + '&preview');
    }

  });
};