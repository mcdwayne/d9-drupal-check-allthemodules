(function ($, Drupal) {

  Drupal.behaviors.ckeditorConfigSettingsSummary = {
    attach: function attach() {
      $('[data-ckeditor-plugin-id="customconfig"]').drupalSetSummary(function (context) {
        var lines = $.trim($('[data-drupal-selector="edit-editor-settings-plugins-customconfig-ckeditor-custom-config"]').val());
        if (lines.length === 0) {
          return Drupal.t('No configuration added');
        }

        var count = $.trim(lines).split('\n').length;
        if (count == 1) {
          return Drupal.t('@count line of configuration', { '@count': count });
        }
        else {
          return Drupal.t('@count lines of configuration', { '@count': count });
        }
      });
    }
  };
})(jQuery, Drupal);