function text_clear_field(field) {
  var value = $('#' + field).val();
  if (value && (value == '<p><br></p>' || value == '<p>\n  <br>\n</p>' || value == '<br>')) {
    $('#' + field).val('');
  }
}


