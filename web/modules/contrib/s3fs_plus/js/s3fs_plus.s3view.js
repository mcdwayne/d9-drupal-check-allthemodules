/**
 * @file s3fs_plus.s3view.js
 *
 * Defines the behavior of the entity browser's S3view widget.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.entityBrowserS3View = {
    attach: function (context) {
      $('form#entity-browser-media-browser-form img').click(function(e) {
        $('form#entity-browser-media-browser-form span').css('background-color', '');
        var FileName = e.target.getAttribute('key');
        $('form#entity-browser-media-browser-form [name="selected_file"]').val(FileName);
        $('form#entity-browser-media-browser-form [key="' + FileName + '"]').parent('span').css({
          "display": "inline-block",
          "background-color": "red",
        });
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
