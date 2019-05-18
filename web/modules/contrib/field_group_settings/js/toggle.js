(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.fieldGroupSettings = {
    attach: function (context, settings) {
      $('button[data-open-next-field-group-settings]')
        .once('field-group-settings-toggle')
        .on('click', function(e) {
          e.preventDefault();
          $(this).next('.field-group-settings__inner').toggleClass('open');
          return false;
        });
    }
  };

})(jQuery, Drupal);
