;(function ($) {
  'use strict';
  var BUTTON_CLASS = 'image-canvas-editor-save';
  var BUTTON_SELECTOR = '.' + BUTTON_CLASS;
  var ATTACHED_CLASS = 'image-canvas-attached';
  var el;
  var callback;
  Drupal.behaviors.imageCanvas = {
    setImage: function (element) {
      el = element;
    },
    setImageCallback: function (cb) {
      callback = cb;
    },
    attach: function (context) {
      if (!$(context).find(BUTTON_SELECTOR).length) {
        return;
      }
      var $btn = $(context).find(BUTTON_SELECTOR);
      if ($btn.hasClass(ATTACHED_CLASS)) {
        return;
      }
      $btn.addClass(ATTACHED_CLASS);
      $btn.click(function (e) {
        // Get image data. Prefer callback, fall back to element.
        var imgData;
        if (callback) {
          imgData = callback();
        }
        else if (el) {
          imgData = el.toDataURL();
        }
        if (!imgData) {
          alert(Drupal.t('No image data captured. This seems like an error with the chosen editor'));
          return;
        }
        $.ajax({
          url: '/image-canvas-editor/save/' + drupalSettings.imageCanvasEditorApi.fid,
          type: 'POST',
          dataType: 'json',
          contentType: "application/json; charset=utf-8",
          data: JSON.stringify({
            image: imgData
          }),
          success: function (data) {
            $('.ui-dialog-titlebar-close.image-canvas-editor-api').click();
            // Also fake invalidation of the thumbnail. By setting it directly
            // to the new full image. Which would also invalidate the cache for
            // editing for the second time.
            $('img[data-fid="' + data.fid + '"]').attr('src', data.url + '?cache_bust=' + Date.now());
          }
        });
      })
    }
  }
})(jQuery);
