(function ($, Drupal, debounce) {
  Drupal.behaviors.improvementsSpinner = {
    attach: function (context, settings) {
      // Used each() because Drupal.debounce() needs separate instance on each spinner.
      $('input.form-spinner', context).each(function () {
        $(this).spinner({
          // Used "spin" event because "stop" event triggered when element losing focus.
          spin: debounce(function () {
            $(this).trigger('change');
          }, 600),
          classes: {
            'ui-spinner': '',
            'ui-spinner-down': '',
            'ui-spinner-up': ''
          }
        });
      });
    }
  };
})(jQuery, Drupal, Drupal.debounce);
