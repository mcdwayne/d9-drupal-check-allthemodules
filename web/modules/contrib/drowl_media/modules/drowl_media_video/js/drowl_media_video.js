/**
* @file
* Drupal media type video JavaScripts:
*
*/
(function ($, Drupal) {
  Drupal.behaviors.drowl_media_video = {
    elemFileUploadFieldSelector: '#edit-field-video-file-0-upload',
    elemFileUploadFieldFileWrapperSelector: '#edit-field-video-file-wrapper',
    elemVideoEmbedUrlFieldSelector: '#edit-field-video-0-value',
    attach: function (context, settings) {
      copyUploadedVideoUrlToVideoEmbed(context, settings);
    },
    /**
     * Copies the video URL in a media upload upload form form the video file upload field into the video embed input field
     * after upload has finished.
     * 
     * @param {object} context 
     * @param {object} settings 
     */
    copyUploadedVideoUrlToVideoEmbed: function (context, settings){
      if ($(Drupal.behaviors.drowl_media_video.elemFileUploadFieldFileWrapperSelector, context).length == 1) {
        var videoUrl = Drupal.behaviors.drowl_media_video.getUploadedVideoUrl(context)
        if (videoUrl) {
          videoUrl = Drupal.behaviors.drowl_media_video.sanitizeVideoUrl(videoUrl)
          Drupal.behaviors.drowl_media_video.setVideoEmbedUrl(videoUrl)
        }
      }
    },
    /**
     * Retrieves the video URL
     */
    getUploadedVideoUrl: function (context, settings){
      var videoUrl = null
      var $elemtLinkField = $(Drupal.behaviors.drowl_media_video.elemFileUploadFieldFileWrapperSelector, context).find('.file.file--video > a:first')
      if ($elemtLinkField.length === 1){
        var href = $elemtLinkField.attr('href')
        if (href){
          videoUrl = href
        }
      }
      return videoUrl
    },
    /**
     * Sanitizes the full video URL to make it relative.
     * @param {string} videoUrl 
     */
    sanitizeVideoUrl: function (videoUrl){
      // Expected format is a full URL: https://www.example.com/xyz/sites/default/files/abc/xyz.mp4
      // Create a relative URL by removing the domain
      videoUrl = videoUrl.replace(/^.*\/\/[^\/]+/, '')
      return videoUrl
    },
    /**
     * Sets the given video URL in the video embed input field.
     * @param {string} videoUrl 
     */
    setVideoEmbedUrl: function (videoUrl){
      $(Drupal.behaviors.drowl_media_video.elemVideoEmbedUrlFieldSelector).val(videoUrl)
    }
  };
})(jQuery, Drupal);