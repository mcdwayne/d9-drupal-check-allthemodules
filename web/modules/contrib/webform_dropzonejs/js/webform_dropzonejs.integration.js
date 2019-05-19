/**
 * @file
 * webform_dropzone.integration.js
 *
 * Defines the behaviors needed for webform dropzonejs integration.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.webformDropzonejsIntegraion = {
    attach: function (context) {

      Dropzone.autoDiscover = false;

      $('.dropzone-enable', context).once('webformDropzoneJs').each(function () {
        var $object = $(this);
        var $form = $object.closest('form');

        // This is the value that is set for the dropzonejs instance.
        var dropzoneId = $object.attr('id');

        if (
          typeof drupalSettings.webformDropzoneJs == "undefined" || 
          drupalSettings.webformDropzoneJs[dropzoneId].length < 1 || 
          drupalSettings.dropzonejs.instances[dropzoneId].instance.length < 1) {
          return;
        }

        // Get the correct dropzonejs instance.
        var thisDropzone = drupalSettings.dropzonejs.instances[dropzoneId].instance;

        // Get the file information.
        var data = drupalSettings.webformDropzoneJs[dropzoneId];
        var webformSubmissionDirectory = data.file_directory
        var files = data.files;

        thisDropzone.options.addRemoveLinks = true;

        if (files !== null && files.length > 0) {
          // Loop through all files attached to this field and attach to the
          // correct dropzoneJS instance.
          files.forEach(function (file) {
            file.status = Dropzone.ADDED;
            file.accepted = true;
            file.is_default = true;
            thisDropzone.emit('addedfile', file);
            thisDropzone.files.push(file);
            
            // Display a thumb of the file if it is an image.
            if (file.is_image) {
              thisDropzone.emit('thumbnail', file, file.path);
            }

            thisDropzone.emit('complete', file);
          });
        }

        // Link to the uploaded file.
        if (webformSubmissionDirectory.length > 0) {
          $('.dz-details').each(function (index, element) {
            (function (index) {
              $(element).attr('id', 'filename_' + index);
              var $selectFile = $('#filename_' + index);
              var fileName = $('.dz-filename > span', $selectFile).text();
              $selectFile.on('click', function () {
                window.open(webformSubmissionDirectory + '/' + fileName);
              });
            }(index));
          });
        }

        // When a file is removed, add a hidden field. Only do this for files
        // already in this dropzone field.
        thisDropzone.on("removedfile", function (file) {
          if (file.is_default && file.id) {
            $("<input type='hidden'>").attr({
              name: 'deleted_dropzone_files[]'
            }).val(file.id).appendTo($form);
          }
        });

      });

    }
  };

}(jQuery, Drupal, drupalSettings));
