Drupal.behaviors.custom_config_js = {
  attach: function (context, settings) {
    'use strict';
    jQuery('.custom-configuration #edit-key').blur(function () {
      var key_name = jQuery(this).val();
      jQuery('.custom-configuration_message').remove();
      getMachineName(key_name);
    });

    /**
     * Get machine name.
     * @param {type} key_name
     * @returns {Boolean}
     */
    function getMachineName(key_name) {
      key_name = key_name.replace(/\//g, '');
      if (key_name === '') {
        return false;
      }
      jQuery.ajax({
        type: 'post',
        url: drupalSettings.path.baseUrl + 'custom_configuration_machine/' + key_name,
        cache: true,
        dataType: 'json',
        success: function (data) {
          jQuery('.custom-configuration #edit-key').after('<div class="error_message custom-configuration_message"><font color="green">Machine name <strong><i>' + data.result.machine_name + '</i></strong>.</font></div>');
        }
      });
    }
  }
};
