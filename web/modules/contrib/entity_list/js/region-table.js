/**
 * @file
 * Custom behavior for region col.
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.regionTable = {

    attach: function (context) {
      $(context).find('[data-region-table]').once('regionTable').each(function (index, element) {
        // var tableDrag =
        var table = this.findTable(element);
        if (table) {
          var tableDrag = Drupal.tableDrag[table.attr('id')];
          if (tableDrag) {
            tableDrag.onDrop = this.onDrop;
            tableDrag.row.prototype.onSwap = this.onSwap;
          }
        }

      }.bind(this));
    },

    findTable: function (context) {
      if (drupalSettings.tableDrag) {
        for (var id in drupalSettings.tableDrag) {
          var table = $(context).find('#' + id);
          if (table.length > 0) {
            return table;
          }
        }
      }
      return false;
    },

    /**
     * Lets row handlers react when a row is dropped into a new region.
     */
    onDrop: function () {
      var $row = $(this.rowObject.element);
      var region = $row.prevAll('[data-region]').data('region');
      if (drupalSettings.entity_list.region_table.region_group) {
        $row.find('.' + drupalSettings.entity_list.region_table.region_group).val(region);
      }
    },

    /**
     * Refreshes placeholder rows in empty regions while a row is being dragged.
     *
     * Copied from field_ui.js.
     *
     * @param {HTMLElement} draggedRow
     *   The tableDrag rowObject for the row being dragged.
     */
    onSwap: function (draggedRow) {
      var rowObject = this;
      $(rowObject.table).find('tr.region-message').each(function () {
        var $this = $(this);

        if ($this.prev('tr').get(0) === rowObject.group[rowObject.group.length - 1]) {
          if (rowObject.method !== 'keyboard' || rowObject.direction === 'down') {
            rowObject.swap('after', this);
          }
        }

        if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
          $this.removeClass('region-populated').addClass('region-empty');
        }
        else if ($this.is('.region-empty')) {
          $this.removeClass('region-empty').addClass('region-populated');
        }
      });
    }

  }
  ;
})(jQuery, Drupal, drupalSettings);
