(function ($, Modernizr, Drupal) {
  Drupal.behaviors.datex = {
    attach: function attach(context, settings) {
      var $context = $(context);
      $context.find('input[data-datex-calendar]').once('datexPicker').each(function () {
        var $input = $(this);

        var c = $input.attr('data-datex-calendar');
        if (c !== 'persian' && c !== 'gregorian') {
          return;
        }

        var sett = {
          autoClose: true,
          format: $input.data('datex-format').replace('Y', 'YYYY').replace('m', 'MM').replace('d', 'DD'),
          position: "auto",
          onlySelectOnDate: true,
          calendarType: "persian",
          calendar: {
            persian: {
              locale: "en"
            }
          },
          timePicker: {
            enabled: false
          },
          initialValueType: c,
          initialValue: false,
          calendarType: c,
        };

        var pd = $input.persianDatepicker(sett);
      });
    },
  };
})(jQuery, Modernizr, Drupal);

