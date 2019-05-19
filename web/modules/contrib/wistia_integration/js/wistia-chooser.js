(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.wistiaUploadBehavior = {
    attach: function (context, settings) {
      var wistiaChooser = new Wistia.Chooser({
        accessToken: settings.token,
        domId: 'wistia-chooser',
        customAction: {
          text: 'Insert this Video (After inserting, click on save)',
          callback: function (media) {
            document.getElementById('wistia-video-url').value = media.id;
          }
        }
      });
      wistiaChooser.setup();
    }
  };
})(jQuery, Drupal);
