/**
 * @file
 * Javascript for Perspective.
 */

/**
 * Provides an asynchronous way to analyze the text.
 */
(function ($) {

  'use strict';

  Drupal.behaviors.perspective = {
    attach: function () {
      var perspectiveField = $('.field-perspective');
      var perspectiveFieldId = perspectiveField.attr('id');
      var fieldContents = '';

      perspectiveField.closest('form').on('submit', function (e) {
        if (perspectiveField.siblings('#cke_' + perspectiveFieldId).length) {
          fieldContents = CKEDITOR.instances[perspectiveFieldId].getData();
        }
        else {
          fieldContents = perspectiveField.val();
        }

        fieldContents = $(fieldContents).text();
        var responseReturn = true;

        $.ajax({
          type: 'post',
          url: '/api/perspective/analyze/' + fieldContents,
          async: false,
          success: function (data) {
            if (data.score > drupalSettings.perspective.tolerance) {
              perspectiveField.addClass('error');
              responseReturn = false;
            }
            else {
              perspectiveField.removeClass('error');
            }
          },
          error: function () {
            alert('There was something wrong with the Ajax request. Please contact administrator.');
          }
        });

        return responseReturn;
      });
    }
  };
})(jQuery);
