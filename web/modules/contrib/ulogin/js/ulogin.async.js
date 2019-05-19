(function ($, Drupal, settings) {
    "use strict";

  Drupal.behaviors.ulogin_async = {
    attach: function (context, settings) {
      Drupal.ulogin = Drupal.ulogin || {};
      Drupal.ulogin.initWidgets = function (context, settings) {
        $.each(settings.ulogin, function (index, value) {
          $('#' + value + ':not(.ulogin-processed)', context).addClass('ulogin-processed').each(function () {
            uLogin.customInit(value);
          });
        });
      };

      if (typeof uLogin != 'undefined') {
        Drupal.ulogin.initWidgets(context, settings);
      }
      else {
        $.ajax({
          url: '//ulogin.ru/js/ulogin.js',
          dataType: 'script',
          cache: true, // Otherwise will get fresh copy on every page load, this is why not $.getScript().
          success: function (data, textStatus, jqXHR) {
            Drupal.ulogin.initWidgets(context, settings);
          }
        });
      }
    }
  }

})(jQuery, Drupal, drupalSettings);
