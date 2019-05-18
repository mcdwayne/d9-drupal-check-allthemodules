/**
 * @file
 * Console history handling.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  $(document).ready(function () {
    $('.input-type-selector div input').each(function (index, Element) {
      if (!Element.checked) {
        $('#console-history-' + $(this).val()).hide();
      }
    });
    $('.input-type-selector div input').click(function (event) {
      $('.console-history').children('ul').hide();
      $('.console-history').children('#console-history-' + $(this).val()).show();
    });
    $('.console-history-selector').click(function (event) {
      event.preventDefault();
      $('#edit-input').val($(this).next('.input').html().replace(/\&gt\;/g, '>'));
    });
  });

}(jQuery, Drupal, drupalSettings));
