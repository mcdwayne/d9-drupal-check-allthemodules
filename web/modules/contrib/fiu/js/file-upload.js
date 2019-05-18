(function ($, Drupal) {

  'use strict';

  let isAdvancedUpload = function () {
    let div = document.createElement('div');
    return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
  }();

  /**
   * Attach behaviors to file upload.
   */
  Drupal.behaviors.fiuFileUpload = {
    attach: function (context) {
      if (isAdvancedUpload) {
        let $fileUploadWrap = $('.js-form-type-managed-file', context);
        let droppedFiles = false;

        $fileUploadWrap.addClass('form-type-managed-file--advanced');
        $fileUploadWrap.find('label').text(Drupal.t('Add or drag new file'));

        $fileUploadWrap.on('drag dragstart dragend dragover dragenter dragleave drop', (event) => {
          event.preventDefault();
          event.stopPropagation();
        })
        .on('dragover dragenter', () => {
          $fileUploadWrap.addClass('is-dragover');
        })
        .on('dragleave dragend drop', () => {
          $fileUploadWrap.removeClass('is-dragover');
        })
        .on('drop', (event) => {
          droppedFiles = event.originalEvent.dataTransfer.files;
          $fileUploadWrap.find('input[type="file"]').prop('files', droppedFiles);
        });
      }
    }
  };

})(jQuery, Drupal);
