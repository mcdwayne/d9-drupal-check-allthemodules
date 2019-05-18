/**
 * @file
 * Defines Javascript behaviors for the recaptcha extra module.
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.recaptcha_extra = {
    attach: function (context, settings) {
      // Reloading captcha.
      if ($('.g-recaptcha', context).html() == '' && typeof grecaptcha !== 'undefined') {
        grecaptcha.render(
            $('.g-recaptcha',
            context)[0],
            { sitekey : $('.g-recaptcha', context).attr('data-sitekey') });
      }
    },
  };
})(jQuery, Drupal);
