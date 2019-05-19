/**
 * @file
 * Map support.
 */

(function($) {
  Drupal.behaviors.yamapsMaps = {
    attach: function (context, settings) {
      ymaps.ready(function() {
        // Basic map class.
        $.yaMaps.YamapsMap = function(mapId, options) {
          this.map = new ymaps.Map(mapId, options.init);
          this.mapId = mapId;
          this.options = options;
          this.mapListeners = this.map.events.group();
          $.yaMaps.maps[mapId] = this;

          // Export map coordinates to html element.
          this.exportCoords = function(event) {
            var coords = {
              center: event.get('newCenter'),
              zoom: event.get('newZoom')
            };
            var $storage = $('.field-yamaps-coords-' + mapId);
            $storage.val(JSON.stringify(coords));
          };

          // Export map type to html element.
          this.exportType = function(event) {
            var type = event.get('newType');
            var $storage = $('.field-yamaps-type-' + mapId);
            $storage.val(type);
          };

          // Map events for export.
          this.map.events
            .add('boundschange', this.exportCoords, this.map)
            .add('typechange', this.exportType, this.map);

          // Enable map controls.
          this.enableControls = function() {
            this.map.controls.add('typeSelector', {float: 'right'});
            if (options.init.behaviors.indexOf('scrollZoom') !== -1) {
              this.map.controls.add('zoomControl', {size: "auto", float: 'right'});
            }
          };

          // Enable plugins.
          this.enableTools = function() {
            var mapTools = $.yaMaps.getMapTools(this);
            var self = this;
            mapTools.forEach(function(mapTool){
              if (mapTool) {
                self.map.controls.add(mapTool);
              }
            });
          };
        };
      });
    }
  }
})(jQuery);
