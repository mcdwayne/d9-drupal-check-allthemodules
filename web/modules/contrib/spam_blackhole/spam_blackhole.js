/**
 * @file
 * Defines Javascript behaviors for the spam_blackhole  module.
 */

(function ($, Drupal, drupalSettings) {
  
 Drupal.behaviors.spam_blackhole = {
  attach: function (context) {
    
    if (drupalSettings.spam_blackhole && drupalSettings.spam_blackhole.forms) {
      $('input[name="form_id"]:not(.spam-blackhole-processed)', context).each(function () {
        if (drupalSettings.spam_blackhole && drupalSettings.spam_blackhole.forms) {
          forms = drupalSettings.spam_blackhole.forms;
          for (var i = 0; i < forms.length; i++) {
            form_id = $(this).attr('value');
            if (forms[i] == form_id) {
              cur_form = $(this).parents('form')[0];
              action = $(cur_form).attr('action');
              $(cur_form).attr('action', action.replace(drupalSettings.spam_blackhole.dummy_url,
               drupalSettings.spam_blackhole.base_url));
            }
          }
        }
      }).addClass('spam-blackhole-processed');
    }
  }
};
})(jQuery, Drupal, drupalSettings);
