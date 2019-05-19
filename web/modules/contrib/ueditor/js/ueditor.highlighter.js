/**
 * @file
 */

(function (Drupal, drupalSettings) {

  'use strict';

  /**
   * ueditor highlighter init.
   */
  Drupal.behaviors.ueditor_highlighter = {
    attach: function (context, settings) {
      SyntaxHighlighter.all();
    }
  };

})(Drupal, drupalSettings);
