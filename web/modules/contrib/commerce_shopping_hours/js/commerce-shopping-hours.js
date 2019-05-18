(function ($) {
  'use strict';

  $('.shopping-hours').timepicker({
    timeFormat: 'H:i',
    step: 15,
    startTime: '00:00',
    dynamic: false,
    dropdown: true,
    scrollbar: true
  });

})(jQuery);
