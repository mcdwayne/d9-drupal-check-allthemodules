/**
 * @file
 */

(function ($, Drupal, Backbone) {
  'use strict';
  var OverlayModel = Backbone.Model.extend({
    defaults: {
      type: '',
      coordinates: [],
      extraParams: {}
    },
    remove: function () {
      this.destroy();
    },

    setExtraParams: function (newExtraParams) {
      var extraParams = _.clone(this.get('extraParams'));
      extraParams = _.extend(extraParams, newExtraParams);
      this.set('extraParams', extraParams);
    },

    getExtraParam: function (param) {
      return this.get('extraParams')[param];
    }
  });

  var OverlayCollection = Backbone.Collection.extend({
    model: OverlayModel
  });

  var OverlayView = Backbone.View.extend({
    tagName: 'tr',

    className: 'overlay-info',
    template: _.template($('#overlay-info-template').html()),
    infoDialogTemplate: _.template($('#overlay-info-dialog-template').html()),

    events: {
      'click span.overlay-edit': 'editOverlay',
      'click span.overlay-remove': 'removeOverlay'
    },

    initialize: function (options) {
      this.model.bind('change', this.render, this);
      this.model.bind('destroy', this.remove, this);
      this.options = options;

    },

    render: function (map) {
      this.$el.html(this.template(this.model.toJSON()));
      if (typeof this.options.overlay == 'undefined') {
        this.options.overlay = this.drawModelOnMap(map);
      }
      var currentView = this;
      this.options.overlay.addListener('mouseover', function () {
        currentView.$el.css('background-color', '#EEE');
      });
      this.options.overlay.addListener('mouseout', function () {
        currentView.$el.css('background-color', 'inherit');
      });

      return this;
    },

    drawModelOnMap: function (map) {
      var overlay = '';
      var coordinates = this.model.get('coordinates');
      if (coordinates.length) {
        var mapParams = {
          map: map
        };
        switch (this.model.get('type')) {
          case 'marker':
            mapParams.position = new google.maps.LatLng(coordinates[0].lat, coordinates[0].lng);
            mapParams.icon = this.model.getExtraParam('icon');
            overlay = new google.maps.Marker(mapParams);
            break;

          case 'circle':
            mapParams.center = new google.maps.LatLng(coordinates[0].lat, coordinates[0].lng);
            mapParams.radius = parseFloat(this.model.getExtraParam('radius'));
            overlay = new google.maps.Circle(mapParams);
            break;

          case 'polygon':
            mapParams.paths = coordinates;
            overlay = new google.maps.Polygon(mapParams);
            break;

          case 'polyline':
            mapParams.path = coordinates;
            overlay = new google.maps.Polyline(mapParams);
            break;

          case 'rectangle':
            mapParams.bounds = new google.maps.LatLngBounds(coordinates[0], coordinates[1]);
            overlay = new google.maps.Rectangle(mapParams);
            break;
        }
      }
      return overlay;
    },

    editOverlay: function () {
      var currentView = this;
      var applyDialog = function () {
        var overlayParams = {
          title: $('input[name="title"]', this).val(),
          description: $('textarea[name="description"]', this).val()
        };
        if ($('.fillColor')) {
          overlayParams.fillColor = $('.fillColor').text();
        }
        if ($('.strokeColor')) {
          overlayParams.strokeColor = $('.strokeColor').text();
        }
        currentView.model.setExtraParams(overlayParams);
        $(this).dialog('close');
      };

      var params = this.model.toJSON();
      switch (this.model.get('type')) {
        case 'marker':
          params.fillColorpicker = false;
          params.strokeColorpicker = false;
          break;

        case 'polyline':
          params.fillColorpicker = false;
          params.strokeColorpicker = true;
          break;

        case 'circle':
        case 'polygon':
        case 'rectangle':
          params.fillColorpicker = true;
          params.strokeColorpicker = true;
          break;
      }
      if (params.fillColorpicker && typeof params.extraParams.fillColor == 'undefined') {
        params.extraParams.fillColor = '#000000';
      }
      if (params.strokeColorpicker && typeof params.extraParams.strokeColor == 'undefined') {
        params.extraParams.strokeColor = '#000000';
      }

      var dialog = this.infoDialogTemplate(params);
      $('.edit-dialog', this.el).html(dialog);
      $('.dialog', this.el).dialog({

        modal: true,
        minWidth: 500,
        buttons: [
          {
            text: Drupal.t('Apply'),
            click: applyDialog
          },
          {
            text: Drupal.t('Cancel'),
            click: function () {
              $(this).dialog('close');
            }
          }
        ],
        create: function (event, ui) {
          var fillColorpicker;
          if (params.fillColorpicker) {
            fillColorpicker = $.farbtastic('.fillColorpicker', function (color) {
              $('.fillColor').text(color);
            });
            fillColorpicker.setColor(params.extraParams.fillColor);
          }
          if (params.strokeColorpicker) {
            fillColorpicker = $.farbtastic('.strokeColorpicker', function (color) {
              $('.strokeColor').text(color);
            });
            fillColorpicker.setColor(params.extraParams.strokeColor);
          }
        },
        close: function (event, ui) {
          $(this).dialog('destroy').remove();
        }
      });
    },

    applyMapObjectParams: function () {
      var extraParams = this.model.get('extraParams');
      var overlayOptions = {};
      if (!_.isUndefined(extraParams.fillColor)) {
        overlayOptions.fillColor = extraParams.fillColor;
      }
      if (!_.isUndefined(extraParams.strokeColor)) {
        overlayOptions.strokeColor = extraParams.strokeColor;
      }
      if (!_.isEmpty(overlayOptions)) {
        this.options.overlay.setOptions(overlayOptions);
      }
    },
    removeOverlay: function () {
      this.options.overlay.setMap(null);
      this.model.destroy();
      this.remove();
    }
  });

  /**
   * Main view, contains overlay info rows.
   */
  var MapObjectsView = Backbone.View.extend({

    initialize: function (options) {
      this.collection.bind('add', this.addOne, this);
      this.collection.bind('remove', this.removeOne, this);
      this.collection.bind('reset', this.restoreAll, this);
      this.mapObjectDataField = options.mapObjectDataField;
      this.initializeMap(options.mapContainer);

    },

    render: function () {
      return this;
    },

    addOne: function () {
      this.mapObjectDataField.val(JSON.stringify(this.collection));
    },

    restoreOne: function (overlay) {
      var overlayView = new OverlayView({model: overlay});
      this.$('tbody').append(overlayView.render(this.map).el);
      this.mapObjectDataField.val(JSON.stringify(this.collection));
      overlayView.applyMapObjectParams();
      overlay.on('change:extraParams', overlayView.applyMapObjectParams, overlayView);
      overlay.on('change:extraParams', this.serializeCollection, this);
      return overlayView;
    },

    restoreAll: function () {
      this.collection.each(this.restoreOne, this);
    },

    removeOne: function () {
      this.mapObjectDataField.val(JSON.stringify(this.collection));
      this.currentObjectsNumber--;
    },

    serializeCollection: function () {
      this.mapObjectDataField.val(JSON.stringify(this.collection));
    },

    newOverlay: function (event) {
      var overlayModel = new OverlayModel({type: event.type});
      switch (event.type) {
        case 'marker':
          overlayModel.setExtraParams({icon: event.overlay.icon});
          overlayModel.set('coordinates', [event.overlay.getPosition()]);
          break;

        case 'circle':
          overlayModel.set('coordinates', [
            {
              lat: event.overlay.getCenter().lat(),
              lng: event.overlay.getCenter().lng()
            }
          ]);
          overlayModel.setExtraParams({radius: event.overlay.getRadius()});
          break;

        case 'polygon':
        case 'polyline':
          overlayModel.set('coordinates', event.overlay.getPath().getArray());
          break;

        case 'rectangle':
          var b = event.overlay.getBounds();
          overlayModel.set('coordinates', [b.getSouthWest(), b.getNorthEast()]);
          break;
      }

      var overlayView = new OverlayView({
        model: overlayModel,
        overlay: event.overlay
      });
      this.$('tbody').append(overlayView.render(this.map).el);
      this.collection.add(overlayModel);

      overlayModel.on('change:extraParams', this.serializeCollection, this);
      overlayModel.on('change:extraParams', overlayView.applyMapObjectParams, overlayView);
    },

    // Initializes Google map and Drawing Manager.
    initializeMap: function (mapContainer) {
      // Init map.
      var map_center_lat = $(mapContainer).parents('.fieldset-wrapper').find('.map_center_lat');
      var map_center_lng = $(mapContainer).parents('.fieldset-wrapper').find('.map_center_lng');
      var map_zoom = $(mapContainer).parents('.fieldset-wrapper').find('.map_zoom');
      var map_type = $(mapContainer).parents('.fieldset-wrapper').find('.map_type');

      var lat = $(map_center_lat).val();
      var lng = $(map_center_lng).val();
      var zoom = parseInt($(map_zoom).val());
      var mapType = $(map_type).val();
      this.maxObjectsNumber = parseInt($(mapContainer).attr('data-max-objects-number'));
      this.currentObjectsNumber = 0;

      var latlng = new google.maps.LatLng(lat, lng);
      var mapOptions = {
        zoom: zoom,
        center: latlng,
        streetViewControl: false,
        mapTypeId: google.maps.MapTypeId[mapType.toUpperCase()]
      };
      this.map = new google.maps.Map(mapContainer, mapOptions);

      // Preview containers(spans with text).
      var map_preview_lat = $(mapContainer).parents('.map-object-field-default-widget').find('.map-preview-lat');
      var map_preview_lng = $(mapContainer).parents('.map-object-field-default-widget').find('.map-preview-lng');
      var map_preview_zoom = $(mapContainer).parents('.map-object-field-default-widget').find('.map-preview-zoom');

      var map = this.map;
      this.map.addListener('zoom_changed', function () {
        $(map_zoom).val(map.zoom);
        $(map_preview_zoom).text(map.zoom);
      });

      this.map.addListener('center_changed', function () {
        $(map_center_lat).val(map.center.lat());
        $(map_center_lng).val(map.center.lng());
        $(map_preview_lat).text(map.center.lat());
        $(map_preview_lng).text(map.center.lng());
      });

      this.map.addListener('maptypeid_changed', function () {
        $(map_type).val(map.mapTypeId);
      });

      // Init Drawing manager.
      var allowedObjectTypes = $(mapContainer).attr('data-allowed-object-types').split(',');
      var drawingModes = [];
      for (var i = 0; i < allowedObjectTypes.length; i++) {
        drawingModes.push(google.maps.drawing.OverlayType[allowedObjectTypes[i].toUpperCase()]);
      }

      this.drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: drawingModes[0],
        drawingControl: true,
        drawingControlOptions: {
          position: google.maps.ControlPosition.TOP_RIGHT,
          drawingModes: drawingModes
        }
      });
      this.drawingManager.setMap(this.map);
      if (this.mapObjectDataField.val()) {
        this.collection.reset($.parseJSON(this.mapObjectDataField.val()));
      }

      this.currentObjectsNumber = this.collection.length;
      var view = this;
      google.maps.event.addListener(this.drawingManager, 'overlaycomplete', function (event) {
        if (!isNaN(view.maxObjectsNumber) && view.currentObjectsNumber >= view.maxObjectsNumber) {
          event.overlay.setMap(null);
        }
        else {
          view.currentObjectsNumber++;
          view.newOverlay(event);
        }
      });
    }
  });

  /**
   * Start app from Drupal behaviour.
   */
  Drupal.behaviors.map_object_field_default_widget = {
    attach: function (context) {
      $('.map-object-field-default-widget .map-preview', context).each(function (index, item) {
        var overlaysInfoRows = $(this).parents('.fieldset-wrapper').find('.overlays-list');
        var mapContainer = $('.map-object-field-default-widget .map-preview', context)[index];
        if (!$(mapContainer).data('map-initialized')) {
          if (typeof overlaysInfoRows != 'undefined') {
            var overlayCollection = new OverlayCollection();
            var savedOverlays = $(this).parents('.fieldset-wrapper').find('.map_object_data');

            new MapObjectsView({
              el: overlaysInfoRows,
              collection: overlayCollection,
              mapContainer: mapContainer,
              mapObjectDataField: savedOverlays
            });
            $(mapContainer).data('map-initialized', '1');
          }
        }
      });
    }
  };

})(jQuery, Drupal, Backbone);
