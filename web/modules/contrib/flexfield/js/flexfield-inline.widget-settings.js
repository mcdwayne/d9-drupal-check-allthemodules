/**
 * @file file_browser.view.js
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Add the selected proprotion class when one is selected on the widget
   * settings form.
   */
  Drupal.behaviors.flexFieldInlineWidgetSettings = {
    attach: function (context) {
      $('.flexfield-inline--widget-settings select').change(function(event) {
        console.log('val');
        var value = $(this).val();
        var $parent = $(this).parents('.flexfield-inline__item');
        $parent.removeClass (function (index, className) {
          return (className.match (/(^|\s)flexfield-inline__item--.*?\S+/g) || []).join(' ');
        });
        $parent.addClass('flexfield-inline__item--' + value)
      });
    }
  };

}(jQuery, Drupal));
