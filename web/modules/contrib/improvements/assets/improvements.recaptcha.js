(function ($, Drupal) {
  Drupal.behaviors.improvementsRecaptcha = {
    attach: function attach(context, settings) {
      if ('grecaptcha' in window && context !== document) {
        $('.g-recaptcha:empty', context).each(function () {
          grecaptcha.render(this, $(this).data());
        });
      }
    }
  };
})(jQuery, Drupal);
