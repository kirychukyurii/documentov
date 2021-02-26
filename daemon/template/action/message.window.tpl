<div class="modal fade" id="modal_window-action_message{{ .Token }}">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div class="pull-right">
          <button class="btn btn-default" data-dismiss="modal" type="button">
            <i class="fa fa-reply"></i>
          </button>
        </div>
        <div class="pull-left">
          <h4 class="modal-title text-left">{{ .Title }}</h4>
        </div>
      </div>
      <div class="modal-body" id="form_route">
        <div class="form-horizontal" id="dialog_form">
          {{ .Content }}
        </div>
      </div>
      <div class="modal-footer">
        <div class="row">
          <div class="col-md-3 col-sm-3">
            <div class="visible-xs" style="margin-top: 15px;"></div>
            <span class="btn btn-default btn-block" data-dismiss="modal">{{ .TxtButtonClose }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script type="text/javascript">
  $('.tooltip').hide();
  $('#modal_window-action_message{{ .Token }}').modal('show');
  $('#modal_window-action_message{{ .Token }}').draggable({handle: '.modal-header'});
  $('#modal_window-action_message{{ .Token }}').on('hidden.bs.modal', function () {
    $(this).remove();
  });
</script>