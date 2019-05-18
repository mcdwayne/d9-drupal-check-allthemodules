/**
 * @file
 * Gridstack backbone models.
 *
 * Implements model functionality for single grid item.
 */

;(function ($, settings, Backbone) {

  'use strict';

  // Create namespace for our app.
  settings.GridstackField = settings.GridstackField || {};
  settings.GridstackField.Models = {};
  settings.GridstackField.Views = {};
  settings.GridstackField.Collections = {};

  /**
   * Backbone model for views row.
   */
  settings.GridstackField.Models.GridItem = Backbone.Model.extend({
    // Default model attributes.
    defaults: {
      height: 1,
      width: 1,
      positionX: 0,
      positionY: 0,
      id: null
    },

    urlRoot: window.location.origin,
    url: null,

    initialize: function () {
      this.url = '/gridstack_field/' + this.id + '/teaser';
    }
  });
}(jQuery, drupalSettings, Backbone));
