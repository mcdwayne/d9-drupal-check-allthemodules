(function ($, Drupal) {
  Drupal.behaviors.MMNodeAlert = {
    attach: function (context) {
      // Intentionally don't use 'once' here
      $('.mm-alert:not(.mm-alert-processed):first', context)
        .addClass('mm-alert-processed')
        .each(function () {
          $('.mm-alert-close :button', this).click(function () {
            Drupal.mmDialogClose();
            if ($('.mm-alert:not(.mm-alert-processed)').length) {
              Drupal.behaviors.MMNodeAlert.attach(context); // show next alert while reappearing
            }
            return false;
          });
          Drupal.mmDialogAdHoc('#' + this.id, '', {
            width: 300,
            height: 300,
            show: {
              effect: "fadeIn",
              duration: "fast"
            },
            hide: {
              effect: "fadeOut",
              duration: "fast"
            }
          });
        });
    }
  };
})(jQuery, Drupal);