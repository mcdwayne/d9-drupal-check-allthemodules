(function ($)
{
    console.log("JS Drupal OpenLayers");
  var i = 0;
  Drupal.behaviors.openlayers = {
    attach: function (context, settings) {
      if (i === 0) {
        $.each(settings.openlayers, function (m, data) {
          $('#' + data.mapId, context).each(function () {
            var container = $(this);
            container.data('openlayers',new Drupal.OpenLayers($(this), data.mapId, data.map, data.features, data.input));
          });
        });
      }
      i++;
    }
  };
  
  Drupal.OpenLayers = function(container, mapId, map_definition, features, input) {
    this.container = container;
    this.mapId = mapId;
    this.inputSource = null;
    this.inputLayer = null;
    this.map_definition = map_definition;
    this.settings = this.map_definition.settings;
    this.zoom = 2;
    if(this.settings.zoom) {
        this.zoom = this.settings.zoom;
    }
    this.center = [0,0];
    if(this.settings.center){
        var coords = this.settings.center.split(",");
        if(coords.length === 2) {
            this.center = [parseFloat(coords[0]),parseFloat(coords[1])];
        }
    }

    this.bounds = null;
    if(this.settings.max_extent) {
        var coords =this.settings.max_extent.split(",");
        if(coords.length === 4) {
            this.bounds = [parseFloat(coords[0]),parseFloat(coords[1]),parseFloat(coords[2]),parseFloat(coords[3])];
        }
    };
    this.layers = this.map_definition.layers;
    this.sources = this.map_definition.sources;
    this.navbar = this.map_definition.navbar;
    this.featureCollection = [];
    this.overlaySource = new ol.source.Vector({});
    if (features !== null && features.length > 0) {
        wktfeatures = this.createWKTFeatures(features);
        this.overlaySource.addFeatures(wktfeatures);
    }
    this.input = input;
    this.styles = {};
    this.olMap = null;
    this.controls = []; //als Controlbar

    this.initialise();
  }
    
    Drupal.OpenLayers.prototype.initialise = function() {
        this.olMap = new ol.Map({
            layers: [],
            controls:this.controls,
            target: this.mapId,
            view: new ol.View({
                center: this.center,
                zoom: this.zoom
            })
        });
        if(this.bounds) {
          this.olMap.setView(
            new ol.View({
                center: this.center,
                extent: this.bounds,   
                zoom: this.zoom,
            })
          );
        } 
        if(Object.keys(this.sources).length >= 0 && Object.keys(this.layers).length >= 0) {
          this.addLayer();
        }
        if(this.featureCollection.length >= 0) {
          this.addFeatureOverlay();
        };
        
        if(this.navbar.controls.length > 0) {
          this.addOLControls();
        }
        if(this.input){
          this.addDraw();
        }
        document.getElementById(this.mapId).data = this.olMap;
    };
    Drupal.OpenLayers.prototype.addDraw = function() {
        this.inputSource = new ol.source.Vector({wrapX: false});
        this.inputLayer = new ol.layer.Vector({
            source: this.inputSource,
            name: 'drawLayer'
        }); 
        this.olMap.addLayer(this.inputLayer);
        
        var format = new ol.format.WKT();
        var wkt = $('#'+this.mapId+'-wktbox').val();
          
        var type = $('#'+this.mapId+'-wktbox').val().toString();
        type = type.replace(/[ (),-.0-9]/g,"");
        if (type === "LINESTRING") {
          $('#'+this.mapId+'-selectfield').val('LineString');
        }
        if (type === "POLYGON") {
          $('#'+this.mapId+'-selectfield').val('Polygon');
        }
        if (type === "POINT") {
          $('#'+this.mapId+'-selectfield').val('Point');
        }
        if (wkt !== '') {
          try {    
            var feature = format.readFeature(wkt, {
              dataProjection: 'EPSG:4326',
              featureProjection: 'EPSG:3857'
            });
            this.olMap.getView().fit(feature.getGeometry().getExtent(),{maxZoom:this.olMap.getView().getZoom()});
            this.inputSource.addFeature(feature);
          } catch(e) {
            console.log(e)
          }
        }
        
        addInteraction = function (map, mapId, draw_ , inputSource, format, inputValue) {
          if(inputValue === true) {
            draw_ = new ol.interaction.Draw({
              source: inputSource,
              type: /** @type {ol.geom.GeometryType} */ ($('#'+mapId+'-selectfield').val()),
            });
          } else {
            draw_ = new ol.interaction.Draw({
              source: inputSource,
              type: /** @type {ol.geom.GeometryType} */ (inputValue),
            });
          }
          draw_.set('name','drupalDraw');
          map.addInteraction(draw_);
          
          draw_.on('drawstart', function(evt){
            $('#'+mapId+'-wktbox').val('');
            inputSource.clear();
          });
            
          draw_.on('drawend', function(evt){
            var feature = evt.feature;
            var p = feature.getGeometry();
            $('#'+mapId+'-wktbox').val(format.writeFeature(feature, {
              dataProjection: 'EPSG:4326',
              featureProjection: 'EPSG:3857'
            }));
          });
        }
        
        var olMap_ = this.olMap;
        var inputSource_ = this.inputSource;
        var mapId_ = this.mapId;
        addInteraction(this.olMap, this.mapId, this.draw, this.inputSource, format, this.input);
        
        $('#'+this.mapId+'-selectfield').change( function(){
          $('#'+mapId_+'-wktbox').val('');
          inputSource_.clear();
          olMap_.getInteractions().forEach(function (interaction) {
            if(interaction.get('name') === 'drupalDraw') {
              olMap_.removeInteraction(interaction);
            }
          });
          addInteraction(olMap_, mapId_, null, inputSource_, format);
        });
    }
        
    Drupal.OpenLayers.prototype.addOLControls = function() {
        for (var id in this.navbar.controls) {
            this.olMap.addControl(new window['ol'][this.navbar.controls[id]['namespace']][this.navbar.controls[id]['machine']]());
        }
    }
    
    /*
     * function to add all defined layers to the map
     * @returns nothing
     */
    Drupal.OpenLayers.prototype.addLayer = function() {   
        for (var layer in this.layers) {
            this.tmp = this.layers[layer];
            switch (this.layers[layer].type) {
                case 'tile':
                    this.olMap.addLayer(
                        new ol.layer.Tile({
                            source: this.addSource(),
                            opacity: this.layers[layer].opacity,
                            isBase: this.layers[layer].isBase,
                            visible:this.layers[layer].isActive,
                            id: this.layers[layer].id,
                            title:this.layers[layer].title,
                            layernames:this.layers[layer].layer,
                        })
                    )
                    break;
                case 'image':
                    this.olMap.addLayer(
                        new ol.layer.Image({
                            source: this.addSource(),
                            opacity: this.layers[layer].opacity,
                            isBase: this.layers[layer].isBase,
                            visible:this.layers[layer].isActive,
                            id: this.layers[layer].id,
                            title:this.layers[layer].title,
                            layernames:this.layers[layer].layer,
                        })
                    )
                    break;
                case 'view':
                case 'node':
                    this.olMap.addLayer(
                        new ol.layer.Vector({
                            source: this.addSource(this.layers[layer].features),
                            opacity: this.layers[layer].opacity,
                            isBase: this.layers[layer].isBase,
                            visible:this.layers[layer].isActive,
                            id: this.layers[layer].id,
                            title:this.layers[layer].title,
                            layernames:this.layers[layer].layer,
                        })
                    );
            }
        };
    };
    
    /*
     * function to add layer sources
     * @param {type} features
     * @returns {ol.source.XYZ|ol.source.ImageWMS|ol.source.Vector|ol.source.OSM}
     */
    Drupal.OpenLayers.prototype.addSource = function(features) {
        if(this.tmp.source !== 'none') {
            switch(this.sources[this.tmp.source].type) {
                case "osm":
                    return new ol.source.OSM();
                    break;
                case "imagewms":
                    return new ol.source.ImageWMS({
                        url: this.sources[this.tmp.source].url,
                        params: {'LAYERS': this.tmp.layer},
                        ratio: 1,
                        serverType: this.sources[this.tmp.source].serverType,
                    })
                    break;
                case "xyz":
                    return new ol.source.XYZ({
                        url: this.sources[this.tmp.source].url,
                    });
                    break;
                case "vector":
                    return new ol.source.Vector({
                        features: this.createWKTFeatures(features),
                    });
                    break;
            }
        } else {
          return new ol.source.Vector({
            features: this.createWKTFeatures(features),
          });
        }
        return null;
    }
    
    Drupal.OpenLayers.prototype.addFeatureOverlay = function() {
      var featureOverlay = new ol.layer.Vector({
        source: this.overlaySource,
      });
      featureOverlay.setMap(this.olMap);
      try{
        var inputExtent = featureOverlay.getSource().getExtent();
        if(!checkInfinity(inputExtent)){
            this.olMap.getView().fit(featureOverlay.getSource().getExtent(),{maxZoom:this.olMap.getView().getZoom()});
        } 
      } catch(ex) {
        console.log("kein Feature gesetzt");  
        console.log(ex);
      }
    };
    /*
     * change every field presentation to this mod is necessary
     */
    Drupal.OpenLayers.prototype.createWKTFeatures = function(features) {
      var format = new ol.format.WKT();
      wktfeatures = [];
      for (i = 0; i < features.length; i++) {
        var feature = format.readFeature(features[i], {
          dataProjection: 'EPSG:4326',
          featureProjection: 'EPSG:3857'
        });
        wktfeatures.push(feature);
      }
      return wktfeatures;
    }
})(jQuery);

function checkInfinity(extentArray){
    for(i = 0; i < extentArray.length; i++) {
        console.log(extentArray[i]);
        console.log(isFinite(extentArray[i]));
        if(!isFinite(extentArray[i])) {
            return true;
            
        }
    }
    return false;    
}