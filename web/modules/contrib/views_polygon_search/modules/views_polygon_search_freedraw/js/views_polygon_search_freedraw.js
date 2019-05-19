/**
 * @file
 * Some basic behaviors and utility functions for Views.
 */
var viewsPolygonSearchControl = L.Control.extend({
  _freeDraw: {},
  _addButton: undefined,
  _removeButton: undefined,
  _removeAllButton: undefined,

  _createButton: function (title, className, container) {
    var link = L.DomUtil.create('a', className, container);
    link.href = '#';
    link.title = title;
    L.DomEvent
      .on(link, 'click', L.DomEvent.preventDefault);
    return link;
  },
  _getWKT: function (polygons) {
    var wktPolygons = [];
    var coords = [];
    for (var i = 0; i < polygons.length; i++) {
      var latlngs = polygons[i];
      for (var num = 0; num < latlngs.length; num++) {
        coords.push(latlngs[num].lng + " " + latlngs[num].lat);
      }
      wktPolygons.push("POLYGON((" + coords.join(", ") + "," + coords[0] + "))");
    }
    return wktPolygons;
  },

  _addPolygon: function(e) {
    this._freeDraw.setMode(L.FreeDraw.MODES.CREATE);
  },

  _removePolygon: function() {
    this._freeDraw.setMode(L.FreeDraw.MODES.DELETE);
  },
  _removeAllPolygons: function() {
    this._freeDraw.clearPolygons();
  },

  _enableButton: function (button, fn, context) {
    if (typeof button == "undefined") {
      return;
    }
    if (typeof button.buttonEnabled == "undefined" || !button.buttonEnabled) {
      var stop = L.DomEvent.stopPropagation;
      L.DomEvent
        .on(button, 'click', stop)
        .on(button, 'touchstart', stop)
        .on(button, 'mousedown', stop)
        .on(button, 'dblclick', stop)
        .on(button, 'click', fn, context);
      L.DomUtil.removeClass(button, 'enabled');
      L.DomUtil.addClass(button, 'enabled');
      button.buttonEnabled = true;
      button.buttonDisabled = false;
    }
  },

  _disableButton: function (button, fn, context) {
    if (typeof button == "undefined") {
      return;
    }
    if (typeof button.buttonDisabled == "undefined" || !button.buttonDisabled) {
      var stop = L.DomEvent.stopPropagation;
      L.DomEvent
        .off(button, 'click', stop)
        .off(button, 'mousedown', stop)
        .off(button, 'touchstart', stop)
        .off(button, 'dblclick', stop)
        .off(button, 'click', fn, context);
      L.DomUtil.removeClass(button, 'enabled');
      button.buttonEnabled = false;
      button.buttonDisabled = true;
    }
  },

  _initButtons: function () {
    var polygons = this._freeDraw.getPolygons(true);
    if (polygons.length > 0) {
      this._enableButton(this._removeButton, this._removePolygon, this);
      this._enableButton(this._removeAllButton, this._removeAllPolygons, this);
      if (typeof this.options.multiple == "undefined" || !this.options.multiple) {
        this._disableButton(this._addButton, this._addPolygon, this);
      }
    }
    else {
      this._enableButton(this._addButton, this._addPolygon, this);
      this._disableButton(this._removeButton, this._removePolygon, this);
      this._disableButton(this._removeAllButton, this._removeAllPolygons, this);
    }
  },

  onAdd: function (map) {
    var self = this;
    this._freeDraw = new L.FreeDraw();
    this._freeDraw.on('markers', function (e) {
      self._initButtons();
      var wktPolygons = self._getWKT(e.latLngs);
      var event = new CustomEvent("FreeDraw:change", {detail: {
        polygons: wktPolygons,
        domId: self.options.domId,
        textAreaId: self.options.textAreaId
      }});
      document.body.dispatchEvent(event);
    });
    map.addLayer(this._freeDraw);

    var container = L.DomUtil.create('div', 'views-polygon-search-control');
    this._addButton =  this._createButton(
      Drupal.t('Add polygon'), 'add-polygon',  container);
    if (typeof this.options.buttons.removeOne != "undefined") {
      this._removeButton = this._createButton(
        Drupal.t('Remove polygon'), 'remove-polygon',  container);
    }
    if (typeof this.options.buttons.removeAll != "undefined") {
      this._removeAllButton = this._createButton(
        Drupal.t('Remove all polygons'), 'remove-all-polygon',  container);
    }

    this._initButtons();
    return container;
  }
});

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.viewsPolygonSearch = {
    attach: function (context, settings) {
      $.each(settings.viewsPolygonSearch, function (k, v) {
        var $view = $('.js-view-dom-id-' + v.domId);
        var map = $('.view-content > div[id*=leaflet-map]', $view);
        var mapData = map.data("leaflet");
        var drawControll = new viewsPolygonSearchControl(v);
        drawControll.addTo(mapData.lMap);
      });
      $('body').on('FreeDraw:change', function (e) {
        if (typeof e.originalEvent.detail != "undefined") {
          var details = e.originalEvent.detail;
          var view = $('.js-view-dom-id-' + details.domId);
          var $textAreaId = $('.views-polygon-search-' + details.textAreaId, view);
          var wkt = '';
          if (details.polygons != "undefined") {
            wkt = e.originalEvent.detail.polygons.join("+");
          }
          $textAreaId.val(wkt);
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
