<script type="text/javascript">
  if ($('.btn').is('#button{{ .AutopressButtonUID }}')) {
    $('.modal').modal('hide');
    $('#button{{ .AutopressButtonUID }}').trigger('click');
  }
</script>
