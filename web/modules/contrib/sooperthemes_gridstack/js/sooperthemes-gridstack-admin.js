/**
 * @file
 */

(function ($, _, settings) {
  Drupal.behaviors.initGridstackAdmin = {
    attach: function (context, settings) {

      var adminFormSelector = '#sooperthemes-gridstack-grid-stack-admin';
      if ($(document).find(adminFormSelector + ' .grid-stack').length <= 0) {
        return false;
      }

      var layoutData = settings.sooperthemesGridStack.layoutDataAdmin;

      $(adminFormSelector + ' .grid-stack', context).gridstack({});

      new function () {
        if (!layoutData) {
          this.serialized_data = [{x: 0, y: 0, width: 1, height: 1}];
        }
        else {
          this.serialized_data = JSON.parse(layoutData);
        }

        var mainGrid = $(adminFormSelector + ' .grid-stack').data('gridstack');

        this.load_grid = function (example_data) {
          mainGrid.remove_all();
          this.serialized_data = example_data ? example_data : this.serialized_data;
          var items = GridStackUI.Utils.sort(this.serialized_data);
          _.each(items, function (node) {
            mainGrid.add_widget($('<div><div class="grid-stack-item-content"/><div/>'),
              node.x, node.y, node.width, node.height);
          }, this);

          $(adminFormSelector + ' .grid-stack').on('change', function (e, items) {
            this.save_grid();
          }.bind(this));

        }.bind(this);

        this.create_grid = function (numberOfItems) {
          var colNum = 0
            , rowNum = 0
            , width = 0;
          mainGrid.remove_all();
          for (i = 0; i < numberOfItems; i++) {
            if (colNum > 11) {
              colNum = 0;
              rowNum++;
            }
            if ((i + 1) == numberOfItems) {
              width = 12 - colNum;
            }
            mainGrid.add_widget($('<div><div class="grid-stack-item-content"/><div/>'),
              colNum, rowNum, width, 1);
            colNum++;
          }
        }.bind(this);

        this.save_grid = function () {

          this.serialized_data = _.map($(adminFormSelector + ' .grid-stack > .grid-stack-item:visible'), function (el) {
            el = $(el);
            var node = el.data('_gridstack_node');
            return {
              x: node.x,
              y: node.y,
              width: node.width,
              height: node.height
            };
          }, this);

          $(adminFormSelector + ' [data-drupal-selector="edit-style-options-more-gridstack-layout-data"]').val(JSON.stringify(this.serialized_data));
        }.bind(this);

        $(adminFormSelector + ' [data-drupal-selector="edit-style-options-gridstack-items"]').on('input', function (e) {
          var numValue = $(adminFormSelector + ' [data-drupal-selector="edit-style-options-gridstack-items"]').val();
          $(adminFormSelector + ' [data-drupal-selector="edit-style-options-gridstack-layout"]').val('custom').attr("selected", "selected");
          if (!numValue || numValue > 100) {
            numValue = 1;
          }
          this.create_grid(numValue);
        }.bind(this));

        $(adminFormSelector + ' [data-drupal-selector="edit-style-options-gridstack-layout"]').on('change', function (e) {
          var layoutValue = $(adminFormSelector + ' [data-drupal-selector="edit-style-options-gridstack-layout"]').val();
          var exampleLayouts = Array();
          exampleLayouts['example_1'] = [
            {"x":0,"y":0,"width":6,"height":8},
            {"x":6,"y":0,"width":3,"height":4},
            {"x":9,"y":0,"width":3,"height":4},
            {"x":6,"y":4,"width":3,"height":4},
            {"x":9,"y":4,"width":3,"height":4}
          ];
          exampleLayouts['example_2'] = [
            {"x":0,"y":0,"width":6,"height":8},
            {"x":6,"y":0,"width":6,"height":4},
            {"x":6,"y":4,"width":3,"height":4},
            {"x":9,"y":4,"width":3,"height":4}
          ];
          exampleLayouts['example_3'] = [
            {"x":0,"y":0,"width":4,"height":6},
            {"x":4,"y":0,"width":4,"height":3},
            {"x":8,"y":0,"width":4,"height":3},
            {"x":4,"y":3,"width":4,"height":3},
            {"x":8,"y":3,"width":4,"height":6},
            {"x":0,"y":6,"width":4,"height":3},
            {"x":4,"y":6,"width":4,"height":3}
          ];
          if (layoutValue && layoutValue != 'custom' && exampleLayouts[layoutValue]) {
            $(adminFormSelector + ' [data-drupal-selector="edit-style-options-gridstack-items"]').val(exampleLayouts[layoutValue].length);
            this.load_grid(exampleLayouts[layoutValue]);
          }
        }.bind(this));

        // TODO: Fix this.
        setTimeout(this.load_grid, 300);
      };
    }
  };
})(jQuery, _, drupalSettings);
