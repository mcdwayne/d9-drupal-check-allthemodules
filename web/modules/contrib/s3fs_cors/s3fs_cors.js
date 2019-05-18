/**
 * @file
 * Provides JavaScript additions to the managed file field type.
 *
 * This file provides progress bar support (if available), popup windows for
 * file previews, and disabling of other file fields during Ajax uploads (which
 * prevents separate file fields from accidentally uploading files).
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach behaviors to file element auto upload.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches triggers for the upload button.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches auto file upload trigger.
   */
  Drupal.behaviors.s3fsCorsAutoUpload = {

    attach: function (context, settings) {
      // Detach default Drupal file auto upload behavior from any s3fs cors file input elements.
      $(context).find('.s3fs-cors-file input[type="file"]').removeOnce('auto-file-upload').off('.autoFileUpload');
      $(context).find('input.s3fs-cors-upload').removeOnce('auto-file-upload').off('.autoFileUpload');
      // Attach the custom s3fs cors auto upload processing behavior.
      $(context).find('.s3fs-cors-file input[type="file"]').once('s3fs-cors-auto-upload').on('change.s3fsCorsAutoUpload', {settings: settings.s3fs_cors, baseUrl: settings.path.baseUrl}, Drupal.s3fsCors.triggerUploadButton);
      $(context).find('input.s3fs-cors-upload').once('s3fs-cors-auto-upload').on('change.s3fsCorsAutoUpload', {settings: settings.s3fs_cors, baseUrl: settings.path.baseUrl}, Drupal.s3fsCors.triggerUploadButton);
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        $(context).find('.s3fs-cors-file input[type="file"]').removeOnce('s3fs-cors-auto-upload').off('.s3fsCorsAutoUpload');
      }
    }
  };

  /**
   * Attach behaviors to links within managed file elements for preview windows.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches triggers.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches triggers.
   */
  Drupal.behaviors.s3fsCorsPreviewLinks = {
    attach: function (context) {
      $(context).find('div.js-form-managed-file .file a').on('click', Drupal.s3fsCors.openInNewWindow);
    },
    detach: function (context) {
      $(context).find('div.js-form-managed-file .file a').off('click', Drupal.s3fsCors.openInNewWindow);
    }
  };

  /**
   * File upload utility functions.
   *
   * @namespace
   */
  Drupal.s3fsCors = Drupal.s3fsCors || {
    validateFile: function (file, settings) {
      $('.file-upload-js-error').remove();
      // Check Size of File
      if (settings.max_size) {
        if (file.size > settings.max_size) {
          return 'Max size allowed is: ' + (settings.max_size / 1048576) + 'MB';
        }
      }
      // Check allowed extensions
      if (settings.extension_list) {
        var extensionPattern = settings.extension_list.replace(/,\s*/g, '|');
        if (extensionPattern.length > 1 && file.name.length > 0) {
          var acceptableMatch = new RegExp('\\.(' + extensionPattern + ')$', 'gi');
          if (!acceptableMatch.test(file.name)) {
            var error = Drupal.t('The selected file %filename cannot be uploaded. Only files with the following extensions are allowed: %extensions.', {
              // According to the specifications of HTML5, a file upload control
              // should not reveal the real local path to the file that a user
              // has selected. Some web browsers implement this restriction by
              // replacing the local path with "C:\fakepath\", which can cause
              // confusion by leaving the user thinking perhaps Drupal could not
              // find the file because it messed up the file path. To avoid this
              // confusion, therefore, we strip out the bogus fakepath string.
              '%filename': file.name.replace('C:\\fakepath\\', ''),
              '%extensions': extensionPattern.replace(/\|/g, ', ')
            });
            return error;
          }
        }
      }

      return false;
    },

    /**
     * Trigger the upload_button mouse event to auto-upload as a managed file.
     *
     * @name Drupal.s3fsCors.triggerUploadButton
     *
     * @param {jQuery.Event} event
     *   The event triggered. For example `change.s3fsCorsAutoUpload`.
     */
    triggerUploadButton: function (event) {

      var file_input = $('input#' + event.target.id);
      var form = file_input.closest('form');
      form.find(':input[type="submit"]').attr('disabled', 'disabled');

      if (file_input[0].files === undefined || window.FormData === undefined) {
        // If we don't have either of these values, we're probably in IE8/9,
        // or some other unsuppoted browser.
        alert('CORS Upload is not supported in your browser. Sorry.');
        return;
      }

      // The target id will initially be like "edit-field-my-name-0-upload" but
      // after the first upload of one or more files, it will be updated to be
      // like "edit-field-my-name-1-upload--2K85Hs_bSF4" where the delta value
      // of "1" is indicating a second upload set.  The delta value will be
      // incremented for each subsequent set.
      var target_id = event.target.id;
      if (target_id.indexOf('--') >= 0) {
        target_id = target_id.split('--')[0];
      }
      var field_name = target_id.split('-');
      var field_name_array = field_name.slice(1, field_name.length - 2);
      var delta = field_name[field_name.length - 2];
      var field_name_key = field_name_array.join('_');

      var baseUrl = event.data.baseUrl;
      var settings = event.data.settings[field_name_key];
      var directory = settings.upload_location.split('::')[1];

      // Encode any "/" chars in directory string
      directory = directory.replace(/\u002F/g, '::');

      // Get the filelist and the number of files to be uploaded.
      var filelist = file_input[0].files;
      var num_files = filelist.length;

      // Process each specified file.
      for (var fix = 0; fix < num_files; fix++) {

        var file_obj = filelist[fix];

        // If validation fails
        var error = Drupal.s3fsCors.validateFile(file_obj, settings);
        if (error) {
          $(this).closest('div.js-form-managed-file').prepend('<div class="messages messages--error file-upload-js-error" aria-live="polite">' + error + '</div>');
          this.value = '';
          return;

        }

        var progress_bar = $('<div>', {
          id: 's3fs-cors-progress-' + fix,
          style: 'width: 100%; line-height: 2em; min-height: 2em; float: left; text-align: left; padding-left: 25px;',
          text: Drupal.t('Preparing upload ...')
        });

        // Hide the file name and display the progress bar.
        file_input.hide().before(progress_bar);

        // Check if file exists and get new key
        $.get(baseUrl + 'ajax/s3fs_cors_key/' + directory + '/' + file_obj.name + '/' + file_obj.size + '/' + fix, function (data, status) {

          if (status !== 'success') {
            alert('Something Went wrong in file upload');

          }
          if (!data.file_key) {
            alert('File with the name already exist');
          }

          var file_key = data.file_key;
          var file_name = data.file_name;
          var file_size = data.file_size;
          var file_index = data.file_index;
          var file_obj = filelist[file_index];

          // Use the HTML5 FormData API to build a POST form to send to S3.
          var fd = new FormData();
          fd.append('key', file_key);
          fd.append('Content-Type', file_obj.type);
          $.each(settings.cors_form_data, function (key, value) {
            fd.append(key, value);
          });
          // File content must be the last item in the FormData.
          fd.append('file', file_obj);

          // Send the AJAX request to S3. Note: cors.form.action is the S3 URL
          // to POST this upload.
          $.ajax({
            url: settings.cors_form_action,
            type: 'POST',
            enctype: 'multipart/form-data',
            data: fd,
            cache: false,
            contentType: false,
            processData: false,
            crossDomain: true,
            xhrFields: {
              withCredentials: true
            },

            xhr: function () {
              // Alter the XMLHTTPRequest to make it use our progressbar code.
              var the_xhr = $.ajaxSettings.xhr();
              if (the_xhr.upload) {
                the_xhr.upload.target_id = event.target.id;
                the_xhr.upload.onprogress = function (event) {
                  if (event.lengthComputable) {
                    var progress = $('#s3fs-cors-progress-' + file_index);
                    // Remove the placeholder text at the last possible moment. But don't mess
                    // with progress.text after that, or we'll destroy the progress bar.
                    if (progress.text()) {
                      progress.text('');
                    }
                    var percent = Math.floor((event.loaded / event.total) * 100);
                    if (percent !== 100) {
                      progress.text('Uploading: ' + percent + '%');
                    }
                    // Now the file will be saved in Drupal and Processed
                    if (percent === 100) {
                      progress.text('Saving File ...');
                    }
                    return true;
                  }
                };
              }
              return the_xhr;
            },

            error: function (jqXHR, textStatus, errorThrown, elem) {
              error = 'An error occured during the upload to S3: ' + errorThrown;
              $(file_input).closest('div.js-form-managed-file').prepend('<div class="messages messages--error file-upload-js-error" aria-live="polite">' + error + '</div>');
              // $(file_input).value = '';
              // @FIXME: Clean Form
            },

            success: function (data, textStatus, jqXHR) {
              file_input.show();

              // Recover the file key. Note: The file_key contains directory as
              // well as file_name so encode any "/" chars in file_key string.
              var xmlDoc = $.parseXML(jqXHR.responseText);
              var $xml = $(xmlDoc);
              var file_key = $xml.find("Key").text();
              file_key = file_key.replace(/\u002F/g, '::');

              // The delta value is the sequence of upload sets to this field.
              field_name = field_name_key + '_' + delta;

              // Save file in Drupal database
              $.get(baseUrl + 'ajax/s3fs_cors_save/' + file_key + '/' + file_name + '/' + file_size + '/' + field_name, function (data, status) {
                if (!data.fid) {
                  alert('File couldn\'t be saved in Drupal');
                  return;
                }

                // Add the fid for this file to hidden fids field.
                var fid = data.fid;
                var fid_selector = target_id.replace('upload', 'fids');
                var fids = $('[data-drupal-selector=' + fid_selector + ']').val();
                fids = (fids) ? fids + ' ' + fid : fid;
                $('[data-drupal-selector=' + fid_selector + ']').val(fids);

                // Post the results to Drupal if all files have been processed.
                var num_fids = fids.split(' ').length;
                if (num_fids == filelist.length) {

                  // Use the HTML5 FormData API to build a POST form to send to Drupal.
                  var fd = new FormData();
                  // Get the non-submit inputs for processing into FormData.
                  var inputs = form.find(':input').not('.js-form-submit');
                  inputs.each(function() {
                    if (this.name) {
                      fd.append(this.name, $(this).val());
                    }
                  });
                  // Get the relevant submit input into FormData.
                  var submits = form.find(':input.js-form-submit');
                  submits.each(function() {
                    if (this.name.substr(0, field_name.length) == field_name) {
                      fd.append('_triggering_element_name', this.name);
                      fd.append('_triggering_element_value', $(this).val());
                    }
                  })
                  // Add some additional required fields into Formdata.
                  fd.append('_drupal_ajax', 1);
                  fd.append('ajax_page_state[theme]', drupalSettings.ajaxPageState.theme);
                  fd.append('ajax_page_state[theme_token]', drupalSettings.ajaxPageState.theme_token);
                  fd.append('ajax_page_state[libraries]', drupalSettings.ajaxPageState.libraries);
                  // Calculate the post url to use.
                  var posturl = '?element_parents=' + settings.element_parents + '&ajax_form=1&_wrapper_format=drupal_ajax';

                  // Generate and send an ajax request with the uploaded file details.
                  $.ajax({
                    url: posturl,
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    data: fd,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: 'json',

                    success: function (response, status, xmlHttpRequest) {
                      // Set the relevant selector in any of the returned Ajax
                      // commands that have a null selector.
                      var responseLength = response.length;
                      for (var i = 0; i < responseLength; i++) {
                        var selector = response[i].selector;
                        if (selector === null) {
                          // Find the first descendant div with an id beginning with "ajax-wrapper".
                          var field_ajax_wrapper = $('div[data-drupal-selector="edit-' + field_name_key.replace(/_/g,'-') + '-wrapper"]');
                          do {
                            field_ajax_wrapper = field_ajax_wrapper.find('div:first-child');
                            var child_div_id = field_ajax_wrapper.prop('id');
                          }
                          while (child_div_id == '' || child_div_id.indexOf('ajax-wrapper') == -1);
                          response[i].selector = '#' + child_div_id;
                        }
                      }
                      // Create a Drupal.Ajax object without associating an
                      // element, a progress indicator or a URL.
                      var ajaxObject = Drupal.ajax({
                        url: posturl,
                        base: false,
                        element: false,
                        progress: false
                      });
                      // Then, simulate an AJAX response having arrived,
                      // and let the Ajax system handle it.
                      ajaxObject.success(response, status,xmlHttpRequest);
                      Drupal.attachBehaviors();
                    },

                    error: function (xmlHttpRequest, status, errorThrown) {
                      alert('Error return from Drupal');
                    }
                  });
                }
              });

              // Remove Progress Bar.
              progress_bar.remove();
              // Re-enable all the submit buttons in the form.
              form.find(':input[type="submit"]').removeAttr('disabled');
            }
          });
        });

      }
    },

    /**
     * Open links to files within forms in a new window.
     *
     * @name Drupal.s3fsCors.openInNewWindow
     *
     * @param {jQuery.Event} event
     *   The event triggered, most likely a `click` event.
     */
    openInNewWindow: function (event) {
      event.preventDefault();
      $(this).attr('target', '_blank');
      window.open(this.href, 'filePreview', 'toolbar=0,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=500,height=550');
    }
  };

})(jQuery, Drupal);
