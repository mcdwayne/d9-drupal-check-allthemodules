(function ($, Drupal) {

  'use strict';
  Drupal.behaviors.mediaBox = {
    attach: function (context, settings) {
      var $insert_button = $("#filefield_filesources_jsonapi_action input[name='insert_selected']");

      $('.js-form-type-checkbox + label').each(function(){
        $(this).find('img').once('image-form-wrapper').wrapAll('<div class="form-image" />').once('image-wrapper').wrapAll('<div class="image" />');
      });
      $('input.form-checkbox').on('click', function() {
        var $parent = $(this).closest('.js-form-type-checkbox');
        $insert_button.mousedown();
        if ($(this).is(':checked')) {
          $parent.addClass('checked');
        }
        else {
          $parent.removeClass('checked');
        }
      });

      // Auto trigger search after entering 3 character.
      $("#filefield_filesources_jsonapi_filter input[name='name']").on('keyup', function(e) {
        if (e.which !== 32) {
          var value = $(this).val();
          var noWhitespaceValue = value.replace(/\s+/g, "");
          var noWhitespaceCount = noWhitespaceValue.length;
          if (noWhitespaceCount >= 3 || noWhitespaceCount === 0) {
            $("#filefield_filesources_jsonapi_filter input[name='op']").mousedown();
          }
        }
      });
    }
  };

})(jQuery, Drupal);
