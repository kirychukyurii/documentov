function FieldDateTime(data) {
  this.data = JSON.parse(data);
  let prefix = this.data.MODULE_NAME + '-'; //'action_report-';
  this.idTimeblock = '#' + prefix + 'timeblock';
  this.idSelectFormat = '#' + prefix + 'select-format';
}

FieldDateTime.prototype.getAdminForm = function () {
  let datetime = this;
  let toggleTimeblock = function () {
    if ($(this).val().indexOf('H') < 0) {
      $(datetime.idTimeblock).show();
    } else {
      $(datetime.idTimeblock).hide();
    }
  };
  $(datetime.idSelectFormat).on('change', toggleTimeblock).trigger('change')

};

FieldDateTime.prototype.getWidgetForm = function () {
};




