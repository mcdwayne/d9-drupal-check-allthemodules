(function ($, Drupal) {

  Drupal.CloudConfig = Drupal.CloudConfig || {};

  Drupal.CloudConfig = {
    showHide: function(element) {
      if($(element).is(':checked')) {
        $('#edit-field-access-key-wrapper').hide();
        $('#edit-field-secret-key-wrapper').hide();
        $('#edit-assume-role').hide();
      }
      else {
        $('#edit-field-access-key-wrapper').show();
        $('#edit-field-secret-key-wrapper').show();
        $('#edit-assume-role').show();
      }
    }
  };

  Drupal.behaviors.cloudConfig = {
    attach: function (context, settings) {
      $('#edit-field-use-instance-credentials-value', context).change(function() {
        Drupal.CloudConfig.showHide(this);
      });

      Drupal.CloudConfig.showHide($('#edit-field-use-instance-credentials-value'));
    }
  };

})(jQuery, Drupal);