(function ($, Drupal) {
  /**
   * Move a block in the blocks table from one region to another via select list.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   */
  Drupal.behaviors.nodeReorderDrag = {
    attach: function () {
      // tableDrag is required and we should be on the blocks admin page.
      if (typeof Drupal.tableDrag == 'undefined' || typeof Drupal.tableDrag['mm-reorder1'] == 'undefined' && typeof Drupal.tableDrag['mm-reorder2'] == 'undefined') {
        return;
      }

      var table = $('table#mm-reorder1,table#mm-reorder2');
      var tableDrag1 = Drupal.tableDrag['mm-reorder1'] || {row: {prototype: {}}}; // Sticky tableDrag object.
      var tableDrag2 = Drupal.tableDrag['mm-reorder2'] || {row: {prototype: {}}}; // Unsticky tableDrag object.

      // Add a handler for when a row is swapped, update empty regions.
      tableDrag1.row.prototype.onSwap = tableDrag2.row.prototype.onSwap = function () {
        var rowObject = this;
        $('tr.region-message', table).each(function () {
          // If the dragged row is in this region, but above the message row, swap it down one space.
          if ($(this).prev('tr').get(0) == rowObject.element) {
            // Prevent a recursion problem when using the keyboard to move rows up.
            if ((rowObject.method != 'keyboard' || rowObject.direction == 'down')) {
              rowObject.swap('after', this);
            }
          }
          // This region has become empty.
          if ($(this).next('tr').is(':not(.draggable)') || $(this).next('tr').length == 0) {
            $(this).removeClass('region-populated').addClass('region-empty');
          }
          // This region has become populated.
          else if ($(this).is('.region-empty')) {
            $(this).removeClass('region-empty').addClass('region-populated');
          }
        });
      };

      tableDrag1.row.prototype.isValidSwap = tableDrag2.row.prototype.isValidSwap = function (row) {
        return !$(row).is('.mm-not-draggable') && !$(row).is('.region-title') && (row != this.table.tBodies[0].rows[0] || $(row).is('.draggable'));
      };

      // A custom message for the blocks page specifically.
      Drupal.theme.tableDragChangedWarning = function () {
        return '<div class="messages messages--warning">' + Drupal.theme('tableDragChangedMarker') + ' ' + Drupal.t('The changes to this table will not be saved until the <em>Save configuration</em> button is clicked.') + '</div>';
      };

      tableDrag1.onDrag = tableDrag2.onDrag = function () {
        var dragObject = this;
        var regionField = $('select.nodes-region-select', dragObject.rowObject.element);
        $(this.rowObject.element).siblings('tr.draggable,tr.region-message').each(function() {
          var regionRow = $(this).is('.region-message') ? this : $(this).prevAll('tr.region-message').get(0);
          var regionName = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
          if ($('option[value=' + regionName + ']', regionField).length == 0) {
            $(regionRow).prev().add(this).addClass('mm-not-draggable');
          }
        });
      };

      // Add a handler so when a row is dropped, update fields dropped into new regions.
      tableDrag1.onDrop = tableDrag2.onDrop = function () {
        $(this.rowObject.element).siblings('tr.mm-not-draggable').removeClass('mm-not-draggable');
        var dragObject = this;
        // Use "region-message" row instead of "region" row because
        // "region-{region_name}-message" is less prone to regexp match errors.
        var regionRow = $(dragObject.rowObject.element).prevAll('tr.region-message').get(0);
        var regionName = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
        var regionField = $('select.nodes-region-select', dragObject.rowObject.element);
        // Check whether the newly picked region is available for this node.
        if ($('option[value=' + regionName + ']', regionField).length == 0) {
          // If not, alert the user and keep the node in its old region setting.
          alert(Drupal.t('The content cannot be placed in this region.'));
          // Simulate that there was a selected element change, so the row is put
          // back to from where the user tried to drag it.
          regionField.change();
        }
        else if ($(dragObject.rowObject.element).prev('tr').is('.region-message')) {
          var weightField = $('select.nodes-weight', dragObject.rowObject.element);
          var oldRegionName = weightField[0].className.replace(/([^ ]+[ ]+)*nodes-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');

          if (!regionField.is('.nodes-region-' + regionName)) {
            regionField.removeClass('nodes-region-' + oldRegionName).addClass('nodes-region-' + regionName);
            weightField.removeClass('nodes-weight-' + oldRegionName).addClass('nodes-weight-' + regionName);
            regionField.val(regionName);
          }
        }
      };
    }
  };
})(jQuery, Drupal);