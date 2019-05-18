(function ($, Drupal, debounce) {
  Drupal.behaviors.improvementsForm = {
    attach: function attach(context, settings) {
      // Trigger "change" event after user stopped typing in textfield.
      // each() used because Drupal.debounce() needs separate instance on each element.
      $('input[type="text"], input[type="number"]', context).once('change-after-typing').each(function () {
        var $input = $(this);
        var timeout = $input.data('typingTimeout') ? $input.data('typingTimeout') : 600;

        $input.on('keyup', debounce(function (event) {
          // If element unfocused then "change" event already triggered.
          if ($input.is(':focus')) {
            $input.trigger('change');
          }
        }, timeout))
      });
    }
  };
})(jQuery, Drupal, Drupal.debounce);
