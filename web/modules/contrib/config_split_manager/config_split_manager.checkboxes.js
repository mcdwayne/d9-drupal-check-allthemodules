/**
 * @file
 * Config Split Manager page checkbox behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Shows checked and disabled checkboxes for all splits (environments).
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches functionality to the splits table.
   */
  Drupal.behaviors.checkboxes = {
    attach: function (context) {
      var self = this;
      $('table#splits').once('splits').each(function () {
        // On a page with many modules and splits, this behavior initially
        // has to perform thousands of DOM manipulations to inject checkboxes
        // and hide them. By detaching the table from the DOM, all operations
        // can be performed without triggering internal layout and re-rendering
        // processes in the browser.
        var $table = $(this);
        var $ancestor;
        var method;
        if ($table.prev().length) {
          $ancestor = $table.prev();
          method = 'after';
        }
        else {
          $ancestor = $table.parent();
          method = 'append';
        }
        $table.detach();

        // Create dummy checkboxes. We use dummy checkboxes instead of reusing
        // the existing checkboxes here because new checkboxes don't alter the
        // submitted form. If we'd automatically check existing checkboxes, the
        // table would be polluted with redundant entries. This is deliberate,
        // but desirable when we automatically check them.
        var $dummy = $('<input type="checkbox" class="dummy-checkbox js-dummy-checkbox" disabled="disabled" checked="checked" />')
          .attr('title', Drupal.t('This module already added in global "All" configurations.'))
          .hide();

        $table
          .find('input[type="checkbox"]')
          .not('.js-rid-all')
          .addClass('real-checkbox js-real-checkbox')
          .after($dummy);

        // Initialize the ALL column checkbox.
        $table.find('input[type=checkbox].js-rid-all')
          .on('click.splits', self.toggle)
          // .triggerHandler() cannot be used here, as it only affects the first
          // element.
          .each(self.toggle);

        // Re-insert the table into the DOM.
        $ancestor[method]($table);
      });
    },

    /**
     * Toggles all dummy checkboxes based on the checkboxes' state.
     *
     * If the "ALL" checkbox is checked, the checked and disabled
     * checkboxes are shown, the real checkboxes otherwise.
     */
    toggle: function () {
      var allCheckbox = this;
      var $row = $(this).closest('tr');
      // jQuery performs too many layout calculations for .hide() and .show(),
      // leading to a major page rendering lag on sites with many modules and
      // splits. Therefore, we toggle visibility directly.
      $row.find('.js-real-checkbox').each(function () {
        this.style.display = (allCheckbox.checked ? 'none' : '');
      });
      $row.find('.js-dummy-checkbox').each(function () {
        this.style.display = (allCheckbox.checked ? '' : 'none');
      });
    }
  };

})(jQuery, Drupal);
