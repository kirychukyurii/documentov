function datetime_clear_field(field) {
  var value = $('#' + field).val();
  if (value && value.search('_') !== -1) {
    $('#' + field).val('');
  }
}