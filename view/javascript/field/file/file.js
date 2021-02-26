function FieldFile(data) {
  this.data = JSON.parse(data);
  let prefix = this.data.MODULE_NAME + '-';
  this.selectPreview = '#' + prefix + 'select-preview';
  this.chekboxes = 'input[type=\'checkbox\']';
  this.mimes_btn = 'a[name=\'mimes\']';
  this.extes_btn = 'a[name=\'extes\']';
  this.removefile_btn = 'span[name=\'button-field_file_remove_file_' + this.data.unique + '\']';
  // this.editfile_btn = 'span[name=\'button-field_file_edit_file_' + this.data.unique + '\']';
  this.field_files = 'input[name=\'' + this.data.NAME + '[]\']';
  this.files = '#field_files_' + this.data.ID;
  this.class_id = '.' + this.data.ID;
  this.modal = '#modal_' + this.data.ID;
  this.class_view = '.' + this.data.BLOCK;
}

FieldFile.prototype.getAdminForm = function () {

  $('input[type=\'checkbox\']').on('click', function () {
    if (this.checked) {
      $(this).parent().parent().parent().css('background-color', '#eee');
    } else {
      $(this).parent().parent().parent().css('background-color', '');
    }
  });
  let _this = this;
  if ($('#select-preview').val() == 1) {
    $('#preview_params').show();
  } else {
    $('#preview_params').hide();
  }

  $('#input-history').attr('disabled', 'disabled');
  $('#input-history').removeAttr('checked');
  $(this.mimes_btn).on("click", function () {
    _this.selectAll(this, "mimes");
    return false;
  });
  $(this.extes_btn).on("click", function () {
    _this.selectAll(this, "extes");
    return false;
  });
  $('#select-preview').on('change', function () {
    if (this.value == '1') {
      $('#preview_params').show();
    } else {
      $('#preview_params').hide();
    }
  });
};

FieldFile.prototype.getWidgetForm = function () {
  let data = this.data;
  if (data.widget_name) {
    return
  }
  let _this = this;
  if (data.filter_form) {
    return; //компактная фильтр-форма
  }
  $(this.removefile_btn).on('click', function () {
    $(this).parent().parent().remove();
    if ($(_this.field_files).length === 0) {
      $(_this.files).empty();
      $(_this.files).append(_this.data.text.text_no_files);
      $(_this.files).append($('<input>').attr('type', 'hidden').attr('name', _this.data.NAME + '[]').val(""));
      $(_this.files).removeClass();
    }
  });

  this.fillClassId();
  if (!$.fileup) {
    addScript('view/javascript/jquery/fileup/src/fileup.js');
    addStyle('view/javascript/jquery/fileup/src/fileup.css');
  }
  $.fileup({
    i18n: JSON.parse(_this.data.text.locale_widget_messages),
    url: 'index.php?route=field/file/upload&field_uid=' + _this.data.field_uid + '&document_uid=' + _this.data.document_uid,
    lang: 'en',
    field_name: _this.data.NAME,
    field_id: _this.data.ID,
    inputID: 'upload-' + _this.data.unique,
    dropzoneID: 'upload-' + _this.data.unique + '-dropzone',
    queueID: 'upload-' + _this.data.unique + '-queue',
    filesLimit: _this.data.limit_files,
    sizeLimit: _this.data.size_file,
    onSelect: function (file) {
      $('#multiple .control-button').show();
    },
    onRemove: function (file, total) {
      $.ajax({
        url: 'index.php?route=field/file/remove&file_uid=' + $('#input-file-' + _this.data.NAME + '_' + file.file_number).val(),
        cache: false
      });
      $('#input-file-' + _this.data.NAME + '_' + file.file_number).remove();
      if (file === '*' || total === 1) {
        $('#multiple .control-button').hide();
      }
      if ($(_this.field_files).length === 0) {
        $(_this.files).empty();
        $(_this.files).append(_this.data.text.text_no_files);
        $(_this.files).append($('<input>').attr('type', 'hidden').attr('name', _this.data.NAME + '[]'));
        $(_this.files).removeClass();
      }
      ;
    },
    onSuccess: function (response, file_number, file) {
      $(_this.files).empty();
      $(_this.files).removeClass();
      _this.fillClassId();
    },
    onAfterRemove: function () {
      _this.fillClassId();
    }
  });

};

// FieldFile.prototype.getMessage = function () {
//   let getCookie = function (name) {
//     const value = `; ${document.cookie}`;
//     const parts = value.split(`; ${name}=`);
//     if (parts.length === 2) return parts.pop().split(';').shift();
//   }
//   let message = {
//     id: "",
//     type: "",
//     downloadLink: "",
//     structureUID: getCookie("structure_uid"),
//     sessionID: getCookie("OCSESSID"),
//     signedFile: "",
//     text: "",
//   }
//   return message
// }

FieldFile.prototype.getWidgetView = function () {
  _this = this;
  if (!_this.data.field_values) {
    return;
  }

  let block_view = $(_this.class_view);
  if (!block_view.length) {
    return;
  }
  $.each(block_view, function () {
    let block = this;
    $(block).html('');
    let i = 1;
    let cmsVerifications = []
    $.each(_this.data.field_values, function () {
      _file = this
      count = _this.data.field_values.length;
      if (this.preview && this.preview == 'cms') {
        id = Math.random().toString(36).slice(2)
        aFileID = _this.files.slice(1) + id
        cmsVerifications.push({
          id: id,
          file: this.link
        })

        $("<a>").attr("id", aFileID).attr("href", this.link).attr("target", "_blank").html(this.name).appendTo(block);
      } else if (this.preview && this.preview == 'pdf') {
        if (typeof pdfjsLib == "undefined") {
          addScript('view/javascript/pdf.js/pdf.min.js');
          pdfjsLib.GlobalWorkerOptions.workerSrc = 'view/javascript/pdf.js/pdf.worker.min.js';
        }
        $pdfViewver = $('<div>').addClass("no-print").appendTo(block);

        $divNavHeader = $('<div>').addClass("btn-group");
        $btPrevH = $("<button>").addClass("btn btn-default").html("<").attr("title", _this.data.text.text_prev_page).appendTo($divNavHeader);
        $btScalePlusH = $("<button>").addClass("btn btn-default").append($("<i>").addClass("fa fa-search-plus")).attr("title", _this.data.text.text_scale_increase).appendTo($divNavHeader);
        $btScaleMinusH = $("<button>").addClass("btn btn-default").append($("<i>").addClass("fa fa-search-minus")).attr("title", _this.data.text.text_scale_descrease).appendTo($divNavHeader);
        $btNextH = $("<button>").addClass("btn btn-default").html(">").attr("title", _this.data.text.text_next_page).appendTo($divNavHeader);
        $btDownload = $("<a>").attr("href", this.link).attr("target", "_blank").addClass("btn btn-default").append($("<i>").addClass("fa fa-download")).attr("title", _this.data.text.text_download_pdf).appendTo($divNavHeader);

        $divNavFooter = $('<div>').addClass("btn-group").css("margin", "5px auto 0px 15px");
        $btPrevF = $("<button>").addClass("btn").html("<").appendTo($divNavFooter);
        $btNextF = $("<button>").addClass("btn").html(">").appendTo($divNavFooter);

        $divNavHeader.appendTo($pdfViewver);

        let mouseZoom = function (e) {
          if (e.shiftKey) {
            if (e.originalEvent.deltaY > 0) {
              $btScaleMinusH.trigger("click")
            } else {
              $btScalePlusH.trigger("click")
            }
          }
        }

        $pdfViewver.on("mousewheel", function (e) { // Chrome
          mouseZoom(e)
        })

        $pdfViewver.on("wheel", function (e) { // FF
          mouseZoom(e)
        })
        let controls = {
          "prevPage": $().add($btPrevH).add($btPrevF),
          "nextPage": $().add($btNextH).add($btNextF),
          "scalePlus": $btScalePlusH,
          "scaleMinus": $btScaleMinusH,
        };
        $divCanvas = $("<div>").css({ overflow: "auto", background: "#ddd", padding: "10px", textAlign: "center" }).appendTo($pdfViewver);
        if (_this.data.preview.width) {
          $divCanvas.css("width", _this.data.preview.width);
        }
        if (_this.data.preview.height) {
          $divCanvas.css("height", _this.data.preview.height);
        }

        $divNavFooter.appendTo($pdfViewver);

        $canvas = $('<canvas>').appendTo($divCanvas);


        _this.renderPDF($canvas.get(0), this.link, controls);
      } else if (this.preview && this.preview_link) {
        // <a href="{{ file.preview_link }}" target="_blank"><img src="{{ file.link }}"></a>
        $img = $("<img>").attr("src", this.link);
        $("<a>").attr("href", this.preview_link).attr("target", "_blank").append($img).appendTo(block);
      } else if (this.preview) {
        // <img src="{{ file.link }}"></img>
        $img = $("<img>").attr("src", this.link).append(block);
      } else {
        // <a href="{{ file.link }}" target="_blank">{{ file.name }}</a>
        $("<a>").attr("href", this.link).attr("target", "_blank").html(this.name).appendTo(block);
      }
      if (i++ < count && _this.data.delimiter) {
        if (_this.data.delimiter[0] == '<' && _this.data.delimiter[_this.data.delimiter.length - 1] == '>') {
          $(_this.data.delimiter).appendTo(block);
        } else {
          block.append(_this.data.delimiter);
        }

      }
    });
    if (cmsVerifications.length) {
      _this.verifyCMS(cmsVerifications)
    }
  });
}

FieldFile.prototype.verifyCMS = function (verifications) {
  _this = this
  console.log("verifyCMS")
  this.connectWS()
    .then((socket) => {
      verifications.forEach(item => {
        let message = Documentov.getMessage()
        message.type = "download"
        message.id = item.id
        message.downloadLink = item.file
        socket.send(JSON.stringify(message))
      })
    })
    .catch(() => "")
}

FieldFile.prototype.connectWS = function () {
  let _this = this
  return new Promise(function (resolve, reject) {
    var socket = new WebSocket('ws://127.0.0.1:12393/eds')
    socket.onopen = function () {
      resolve(socket);
    };
    socket.onerror = function (err) {
      reject(err);
    };
    socket.onmessage = function (event) {
      message = JSON.parse(event.data)
      switch (message.type) {
        case "download":
          message.type = "check"
          socket.send(JSON.stringify(message))
          break
        case "check":
          if (message && message.text) {
            verification = _this.parseSignatureVerification(message.text)
            if (!verification) {
              // пришла ошибка
              verification = message.text
            }
            $("#" + _this.files.slice(1) + message.id).after($("<span>").addClass("text-muted").html("<br>" + verification))
          }
          break
        default:
          break;
      }
    }
  });
}

FieldFile.prototype.parseSignatureVerification = function (verification) {
  if (!verification) {
    return ""
  }
  parts = verification.split(";")
  result = ""
  for (i = 0; i < parts.length; i++) {
    p = parts[i]
    if (p.indexOf("signing time") >= 0) { // время подписи
      if (result) {
        result += "<br>"
      }
      result += p.trim().replace("signing time ", "").toUpperCase() + " "
    }
    if (p.indexOf("CN=") >= 0) {
      result += p.trim().slice(3)
    }
    if (p.indexOf("O=") >= 0) {
      result += " " + p.trim().slice(2).replace(/ТОВАРИЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ/, "ТОО")
    }
    if (p.indexOf("Verify chain and certificates: -") > 0) {
      v = p.split(": -")
      result += ": " + v[1]
    }
  }
  return result
}

FieldFile.prototype.renderPDF = function (canvas, file, controls) {
  let pdfState = {
    pdf: null,
    currentPage: 1,
    zoom: 1
  }
  pdfjsLib.getDocument(file).promise.then((pdf) => {
    pdfState.pdf = pdf;
    render();
    if (pdfState.pdf._pdfInfo.numPages < 2) {
      controls.nextPage.prop('disabled', true);
      controls.prevPage.prop('disabled', true);
    }
  });

  controls.scalePlus.on("click", function () {
    pdfState.zoom += 0.25;
    render();
  });


  controls.scaleMinus.on("click", function () {
    pdfState.zoom -= 0.25;
    render();
  });


  controls.nextPage.on("click", function () {
    pdfState.currentPage++
    if (pdfState.currentPage >= pdfState.pdf._pdfInfo.numPages) {
      controls.nextPage.prop('disabled', true);
    }
    controls.prevPage.prop('disabled', false);
    render();
  });

  controls.prevPage.on("click", function () {
    pdfState.currentPage--
    if (pdfState.currentPage <= 1) {
      controls.prevPage.prop('disabled', true);
    }
    controls.nextPage.prop('disabled', false);
    render();
  });

  let render = function () {
    pdfState.pdf.getPage(pdfState.currentPage).then((page) => {
      var ctx = canvas.getContext('2d');

      // var viewport = page.getViewport(pdfState.zoom);
      var viewport = page.getViewport({ scale: pdfState.zoom });
      canvas.width = viewport.width;
      canvas.height = viewport.height;

      page.render({
        canvasContext: ctx,
        viewport: viewport
      });

    });
  };
}

FieldFile.prototype.selectAll = function (link, name) {
  if ($(link).html() === this.data.text.text_deselect_all) {
    $('input[name*=\'file_' + name + '\']').prop('checked', '');
    $('table[name=\'tfile_' + name + '\'] td').css('background-color', '');
    $(link).html(this.data.text.text_select_all);
  } else {
    $('input[name*=\'file_' + name + '\']').prop('checked', 'checked');
    $('table[name=\'tfile_' + name + '\'] td').css('background-color', '#eee');
    $(link).html(this.data.text.text_deselect_all);
  }
};


FieldFile.prototype.fillClassId = function () {
  $(this.class_id).val('');
  let files_id = [];
  $.each($(this.field_files), function () {
    files_id.push($(this).val());
  });
  $(this.class_id).val(files_id.join(','));
  $(this.class_id).trigger('change');
};

FieldFile.prototype.getFileNameByFileUID = function (fileuid) {
  let filedescr = this.data.files.find(item => item.file_uid == fileuid);
  if (filedescr) {
    return filedescr.file_name;
  }
};
