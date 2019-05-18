(function ($, window, Drupal) {
    Drupal.behaviors.draggableDashboard = {
        attach: function attach(context, settings) {
            // Do not allow to add draggable dashboard block into draggable dashboard.
            $('.block-add-table').find('li.add').each(function(){
                $(this).find('a[href*="draggable_dashboard_block"]').each(function(){
                    $(this).parents('tr:first').remove();
                });
            });
        }
    };
    Drupal.behaviors.draggableBlockDrag = {
        attach: function attach(context, settings) {

            if (typeof Drupal.tableDrag === 'undefined' || typeof Drupal.tableDrag.dashboardblocks === 'undefined') {
                return;
            }

            function checkEmptyRegions(table, rowObject) {
                table.find('tr.region-message').each(function () {
                    var $this = $(this);

                    if ($this.prev('tr').get(0) === rowObject.element) {
                        if (rowObject.method !== 'keyboard' || rowObject.direction === 'down') {
                            rowObject.swap('after', this);
                        }
                    }

                    if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
                        $this.removeClass('region-populated').addClass('region-empty');
                    } else if ($this.is('.region-empty')) {
                        $this.removeClass('region-empty').addClass('region-populated');
                    }
                });
            }

            function updateLastPlaced(table, rowObject) {
                table.find('.color-success').removeClass('color-success');

                var $rowObject = $(rowObject);
                if (!$rowObject.is('.drag-previous')) {
                    table.find('.drag-previous').removeClass('drag-previous');
                    $rowObject.addClass('drag-previous');
                }
            }

            function updateBlockWeights(table, region) {
                var weight = -Math.round(table.find('.draggable').length / 2);

                table.find('.region-' + region + '-message').nextUntil('.region-title').find('select.block-weight').val(function () {
                    return ++weight;
                });
            }

            var table = $('#dashboardblocks');

            var tableDrag = Drupal.tableDrag.dashboardblocks;

            tableDrag.row.prototype.onSwap = function (swappedRow) {
                checkEmptyRegions(table, this);
                updateLastPlaced(table, this);
            };

            tableDrag.onDrop = function () {
                var dragObject = this;
                var $rowElement = $(dragObject.rowObject.element);

                var regionRow = $rowElement.prevAll('tr.region-message').get(0);
                var regionName = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
                var regionField = $rowElement.find('select.block-region-select');

                if (regionField.find('option[value=' + regionName + ']').length === 0) {
                    window.alert(Drupal.t('The block cannot be placed in this region.'));

                    regionField.trigger('change');
                }

                if (!regionField.is('.block-region-' + regionName)) {
                    var weightField = $rowElement.find('select.block-weight');
                    var oldRegionName = weightField[0].className.replace(/([^ ]+[ ]+)*block-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');
                    regionField.removeClass('block-region-' + oldRegionName).addClass('block-region-' + regionName);
                    weightField.removeClass('block-weight-' + oldRegionName).addClass('block-weight-' + regionName);
                    regionField.val(regionName);
                }

                updateBlockWeights(table, regionName);
            };

            $(context).find('select.block-region-select').once('block-region-select').on('change', function (event) {
                var row = $(this).closest('tr');
                var select = $(this);

                tableDrag.rowObject = new tableDrag.row(row[0]);
                var region_message = table.find('.region-' + select[0].value + '-message');
                var region_items = region_message.nextUntil('.region-message, .region-title');
                if (region_items.length) {
                    region_items.last().after(row);
                } else {
                    region_message.after(row);
                }
                updateBlockWeights(table, select[0].value);

                checkEmptyRegions(table, tableDrag.rowObject);

                updateLastPlaced(table, row);

                if (!tableDrag.changed) {
                    $(Drupal.theme('tableDragChangedWarning')).insertBefore(tableDrag.table).hide().fadeIn('slow');
                    tableDrag.changed = true;
                }

                select.trigger('blur');
            });
        }
    };
})(jQuery, window, Drupal);