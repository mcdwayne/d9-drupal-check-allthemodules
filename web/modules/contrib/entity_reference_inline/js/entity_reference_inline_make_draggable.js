/**
 * @file
 * Entity Reference Inline behavior.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Make new rows added by our widget draggable.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.entity_reference_inline_new_ajax_rows = {
    attach: function (context, settings) {
      var $context = $(context);

      if ($context.hasClass('entity-reference-inline-new-ajax-row')) {
        var table_id = $context.attr('table-id');
        var table_drag = Drupal.tableDrag[table_id];

        if (table_drag) {
          $context.removeClass('entity-reference-inline-new-ajax-row');
          $context.removeAttr('table-id');

          table_drag.makeDraggable(context);
          table_drag.initColumns();
        }
      }
    }
  };

})(jQuery, Drupal);
