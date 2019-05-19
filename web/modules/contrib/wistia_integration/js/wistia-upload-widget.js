(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.wistiaUploadBehavior = {
    attach: function (context, settings) {
      window._wapiq = window._wapiq || [];
      _wapiq.push(function (W) {
        window.wistiaUploader = new W.Uploader({
          accessToken: settings.token,
          dropIn: 'wistia_uploader',
          projectId: settings.projectId,
          beforeUpload: function () {
            // @todo Add file matadata.
          }
        });
        wistiaUploader.bind('uploadsuccess', function (file, media) {
          $('.wistia-video-id').val(media.id);
        });
      });
    }
  };
})(jQuery, Drupal);
