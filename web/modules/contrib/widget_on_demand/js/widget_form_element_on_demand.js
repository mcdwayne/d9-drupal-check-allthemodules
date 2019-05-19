/**
 * @file
 * Widget On Demand behavior.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Add a click event listener to widget form elements on demand.
   *
   * On click at a view element wrapped as widget on demand form element it
   * will be replaced with the real form element by executing an ajax callback.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.WidgetOnDemand = {
    attach: function (context, settings) {

      // Attach a click event listener to each form element, which should be
      // loaded on demand.
      $('.widget-form-element-on-demand').once('widget-form-element-on-demand-on-click').each(function () {
        $(this).on('click', function () {
          // After the user clicks on a view element it should be replaced with
          // the corresponding form element. So we execute the hidden submit.
          var submit = $('input[name="' + $(this).attr('id') + '"]');
          submit.mousedown();
        });
      });
    }
  };
})(jQuery, Drupal);
