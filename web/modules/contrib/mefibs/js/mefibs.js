/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 */
(function ($, Drupal, drupalSettings) {

Drupal.behaviors.MefibsForm = {};
Drupal.behaviors.MefibsForm.views_settings = new Array();
Drupal.behaviors.MefibsForm.attach = function() {

  if (!drupalSettings.mefibs) {
    return;
  }
  if (drupalSettings && drupalSettings.views && drupalSettings.views.ajaxViews) {
    $.each(drupalSettings.views.ajaxViews, function(i, settings) {
      $.each(drupalSettings.mefibs.forms, function(index, mefibs) {
        if (settings.view_name == mefibs.view_name && settings.view_display_id == mefibs.view_display_id) {
          Drupal.behaviors.MefibsForm.views_settings[mefibs.form_prefix] = settings;
          var settings_copy = jQuery.extend({}, settings);
          settings_copy.view_display_id = mefibs.view_display_id + '-' + mefibs.form_prefix;
          instance = new Drupal.views.ajaxView(settings_copy);
          instance.$exposed_form.find('input,select,checkbox,radio').each(function() {
            var name = $(this).attr('name');
            if (name && name.indexOf(mefibs.form_prefix) == -1 && name != mefibs.form_prefix.replace('-', '_')) {
              $(this).attr('name', mefibs.form_prefix + '-' + name);
            }
          });
        }
      });
    });
  }
};

if (Drupal.ajax) {
  /**
   * Modify form values prior to form submission.
   */
  Drupal.ajax.prototype.beforeSerialize = function(element, options) {
    if (typeof options.data.view_name == 'undefined') {
      return false;
    }
    var view_name = options.data.view_name;
    var view_display_id = options.data.view_display_id;

    // clean the display id so that views will actually respond
    $.each(drupalSettings.mefibs.forms, function(index, mefibs) {
      if (view_name == mefibs.view_name && (view_display_id == (mefibs.view_display_id + '-' + mefibs.form_prefix))) {
        options.data.view_display_id = mefibs.view_display_id;
        options.data.view_args = Drupal.behaviors.MefibsForm.views_settings[mefibs.form_prefix].view_args;
      }
    });
  };
}

})(jQuery, Drupal, drupalSettings);
