(function ($)
{
  var i = 0;
  Drupal.behaviors.openlayersformsettings = {
    attach: function (context, settings) {
      if (i === 0) {
        console.log(settings.openlayers.mapId+"huhu");
        var map = new ol.Map({
          layers: [
            new ol.layer.Tile({
              source: new ol.source.OSM()
            })
          ],
          target: settings.openlayers.mapId,
          view: new ol.View({
            center: [0, 0],
            zoom: 2
          })
        });
        map.getView().on('change:resolution', function(evt){
          console.log(map.getView().getResolutionForExtent());
        });
      }
      i++;
    }
  };
})(jQuery);