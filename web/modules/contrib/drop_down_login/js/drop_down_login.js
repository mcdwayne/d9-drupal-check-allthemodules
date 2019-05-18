/**
 * @file
 * Javascript and jQuery functions which are useful for the drop_down_login module.
 */

(function ($, Drupal) {
  'use strict';
  // Drupal.behaviors.
  Drupal.behaviors.drop_down_login = {
    attach: function (context, settings) {
      // Drop down login animation.
      $('#drop-down-login-wrapper', context).addClass('enable-dd');
      $('#drop-down-login-wrapper .login', context).click(function () {
        $(this).toggleClass('open').siblings('.dropdown').slideToggle('slow', function () {
          if ($('#drop-down-login-wrapper .login').hasClass('open')) {
            $('#drop-down-login-wrapper .login').siblings('.dropdown').find('.form-text').eq(0).focus();
          }
        });
        return false;
      });
      $('body', context).click(function (evt) {
        if ($('#drop-down-login-wrapper .login').hasClass('open')) {
          if ($(evt.target).closest('.dropdown').length === 0) {
            $('#drop-down-login-wrapper .login').toggleClass('open').siblings('.dropdown').slideToggle('slow');
          }
        }
      });
    }
  };
}(jQuery, Drupal));
