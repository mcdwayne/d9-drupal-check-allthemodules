(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.dirtyFormApi = {
    attach: function (context, settings) {
      var self = this;

      // We iterate each form uniquely and then use once() on the individual
      // inputs in case we have an ajax form that replaces the inputs but not
      // the entire form.
      $('form').each(function() {
        var form = this;
        var callback = function() {
          $(form).trigger('dirty')
        };

        self.bindInputs(form, callback);
        self.bindCkeditor(form, callback);
      });
    },

    bindInputs: function(form, callback) {
      $('input, textarea', form).once().each(function() {
        $(this).blur(callback);
      });
    },

    bindCkeditor: function(form, callback) {
      if (typeof CKEDITOR === 'undefined' || typeof CKEDITOR.instances === 'undefined') {
        return;
      }

      $.each(CKEDITOR.instances, function() {
        this.on('blur', function() {
          if (this.checkDirty()) {
            callback();
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
