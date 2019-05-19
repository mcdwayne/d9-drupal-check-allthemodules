/**
 * @file
 * Twig field behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.twigFieldEditor = {
    attach: function (context) {

      $('[data-tf-insert]').once().click(function (event) {
        var widgetId = $(this).data('tf-insert');
        var $select = $(context).find('[data-tf-variables="' + widgetId + '"]');
        var variable = $select.val();
        if (variable) {
          var $textArea = $(context).find('[data-tf-textarea="' + widgetId + '"]');
          var editor = $textArea.next()[0].CodeMirror;
          var doc = editor.getDoc();
          doc.replaceSelection('{{ ' + $select.val() + ' }}', doc.getCursor());
          $select.val('');
        }
        event.preventDefault();
      })

    }
  };

} (jQuery, Drupal));
