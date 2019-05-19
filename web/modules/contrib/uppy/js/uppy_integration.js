/**
 * @file
 */

(function($) {

  function uppySendTusComplete (file, resp, fidTarget) {
    delete file.data;
    resp.uploadURL = resp.url;
    var ajax_settings = {
      type: 'POST',
      contentType: 'application/json;charset=utf-8',
      dataType: 'json',
      processData: false,
      data: JSON.stringify({file: file, response: resp}),
      url: '/tus/upload-complete'
    };

    // Send ajax call to inform upload complete, and put value in the field.
    $.ajax(ajax_settings).done(function(response) {
      fidTarget.val(response.fid);
    });
  };

  /**
   * Attaches the Uppy widget to each Uppy form element.
   */
  Drupal.behaviors.uppy = {
    attach: function (context, settings) {
      $('.uppy-widget', context).once('uppy-widget-init').each(function () {
        var $this = $(this);

        // Merge the default settings and the element settings to get a full
        // settings object to pass to the Plupload library for this element.
        var id = $this.attr('id');
        var defaultSettings = settings.uppy['_default'] ? settings.uppy['_default'] : {};
        var elementSettings = (id && settings.uppy[id]) ? settings.uppy[id] : {};
        var uppySettings = $.extend({}, defaultSettings, elementSettings);
        // Target for fids.
        var fidField = uppySettings.fieldName + '[' + uppySettings.delta + "][fids]";
        // Find parent file field, and hide core upload widget.
        var parent = $this.closest('.uppy-file');
        parent.find('input[type="file"]').first().hide();
        // Get or create the target fids hidden input.
        var fidTarget = parent.find('input[name="' + fidField + '"]');
        if (! fidTarget.length) {
          fidTarget = $('<input>').attr('name', fidField).attr('type','hidden').appendTo(parent);
        }

        // Initialize Uppy.
        var uppy = Uppy.Core({
          id: id,
          autoProceed: uppySettings.auto_proceed || false,
          restrictions: {
            maxFileSize: uppySettings.max_file_size || null,
            maxNumberOfFiles: uppySettings.max_number_of_files || null,
            allowedFileTypes: uppySettings.allowed_file_types || null
          },
          meta: {
            entityType: uppySettings.entityType,
            entityBundle: uppySettings.entityBundle,
            fieldName: uppySettings.fieldName
          }
        });
        // Use the @uppy/dashboard widget.
        uppy.use(Uppy.Dashboard, {
          target: '#' + id,
          inline: true,
          replaceTargetContent: true,
          proudlyDisplayPoweredByUppy: false
        });
        // If we're using TUS.
        if (uppySettings.uploader.length && uppySettings.uploader == 'tus') {
          uppy.use(Uppy.Tus, {
            endpoint: '/tus/upload',
            resume: true,
            autoRetry: true,
            limit: 0,
            retryDelays: [500, 1500, 3000],
            chunkSize: uppySettings.chunk_size
          });
          // Inform TUS (and Drupal) that the upload is complete.
          uppy.on('upload-success', function(file, resp) {
            uppySendTusComplete(file, resp, fidTarget);
          });
        }

      });
    }
  };

})(jQuery);
