/**
 * @file
 * Gridstack backbone collections.
 *
 * Implements collection for containing models of grid items.
 */

;(function ($, settings, Backbone) {

  'use strict';

  /**
   * Backbone collections.
   */
  settings.GridstackField.Collections.GridItems = Backbone.Collection.extend({

    // Method for adding items into our collection and grid.
    addItems: function (data) {
      var model;
      _.each(data, function (el) {
        model = new settings.GridstackField.Models.GridItem({
          id: el.id,
          height: el.height,
          positionX: el.positionX,
          positionY: el.positionY,
          width: el.width
        });
        this.add(model);
      }, this);

      new settings.GridstackField.Views.GridFieldItems({collection: this});
    },

    // Method for adding single item into grid and collection.
    addItem: function (nid) {
      var itemModel = new settings.GridstackField.Models.GridItem({id: nid});
      this.add(itemModel);
      new settings.GridstackField.Views.GridField({model: itemModel, collection: this});
    }
  });
}(jQuery, drupalSettings, Backbone));
