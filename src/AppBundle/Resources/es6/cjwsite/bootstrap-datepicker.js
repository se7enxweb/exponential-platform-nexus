import $ from 'jquery';
import 'bootstrap-datepicker';
import 'bootstrap-datepicker/js/locales/bootstrap-datepicker.de';


(() => {
  // Base initialization code
})();

$(() => {
  // On ready

  // Initialize datepicker functionality
  $('.input-daterange').datepicker({
    format: 'D, dd.mm.yyyy',
    clearBtn: true,
    startDate: 'today',
    orientation: 'bottom auto',
    language: 'de',
    regional: 'de',
    autoclose: true,
    autocomplete: 'off',
    todayHighlight: true,
  });

  // Let "daysShort" be the very same format as "daysMin"
  $.fn.datepicker.dates.de.daysShort = $.fn.datepicker.dates.de.daysMin;
});
