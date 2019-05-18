(function ($, Drupal) {

  'use strict';

  var beforeSend = Drupal.Ajax.prototype.beforeSend;
  Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {
    beforeSend.call(this, xmlhttprequest, options);

    if (this.progress.type == 'fiu_progress') {
      this.progress.element = $('<div class="fiu-progress-wrapper"><span class="sp sp-circle"></span></div>');
      $(this.element).after(this.progress.element);
      $(this.element).parents('.fiu-add-element').addClass('fiu-load-image');
    }
  };

  var ajaxSuccess = Drupal.Ajax.prototype.success;
  Drupal.Ajax.prototype.success = function (response, status) {
    ajaxSuccess.call(this, response, status);
    $('.fiu-add-element').removeClass('fiu-load-image');
  };

  var ajaxError = Drupal.Ajax.prototype.error;
  Drupal.Ajax.prototype.error = function (xmlhttprequest, uri, customMessage) {
    ajaxError.call(this, xmlhttprequest, uri, customMessage);
    $('.fiu-add-element').removeClass('fiu-load-image');
  };

  Drupal.behaviors.fiu_sort = {
    attach: function (context, settings) {
      $('#sortable').sortable({
        update: function (event, ui) {
          var order = 0;
          $('#sortable > li .js-form-type-select select').each(function () {
            $(this).val(order);
            order++;
          });
        }
      });
    }
  };

  /**
   * Attach behaviors to file element auto upload.
   */
  Drupal.behaviors.fiuFileAutoUpload = {
    attach: function (context) {
      var image = $(context).find('.fiu-wrapper .fine-image-data .file--image a');
      if (image.length === 1) {
        $(image).closest('.form-managed-file').find('.form-submit.upload-button').trigger('mousedown');
      }
    }
  };

})(jQuery, Drupal);
