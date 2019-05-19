(function ($, Drupal) {
  Drupal.behaviors.improvementsFile = {
    attach: function attach(context, settings) {
      // Disable file autoload.
      // @see Drupal.behaviors.fileAutoUpload.attach
      var $fileInput = $('.js-form-file--no-autoupload', context);
      if ($fileInput.length) {
        Drupal.behaviors.fileAutoUpload.detach($fileInput.parent(), settings, 'unload');
        Drupal.behaviors.fileButtons.detach(context, settings, 'unload');
      }
    }
  };

  Drupal.file.disableFields = $.noop;
})(jQuery, Drupal);
