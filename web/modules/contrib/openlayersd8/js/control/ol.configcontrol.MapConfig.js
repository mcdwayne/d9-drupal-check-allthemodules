ol.configcontrol.MapConfig = function(opt_options, settings) {
    var options = opt_options || {};
    var child = document.createElement('div');
    child.innerHTML = 'ME';
    var this_ = this;
    var setMaxExtent = function(){        
        var extent = this_.getMap().getView().calculateExtent(this_.getMap().getSize());
        document.getElementById("edit-max-extent-0-value").value = extent;
    }

    child.addEventListener('click', setMaxExtent, false);
    child.addEventListener('touchstart', setMaxExtent, false);

    var element = document.createElement('div');
    element.className = 'ol-unselectable ol-control';
    element.appendChild(child);

    ol.control.Control.call(this, {
      element: element,
      target: options.target
    });
};
ol.inherits(ol.configcontrol.MapConfig, ol.control.Control);

window.onload = function(){ 
    var map_ = document.getElementById("openlayers-map").data;
    document.getElementById("openlayers-map").style.height = document.getElementById("edit-map-height-0-value").value + "px";
    map_.updateSize();
    document.getElementById("edit-map-height-0-value").onchange = function(){
        document.getElementById("openlayers-map").style.height = document.getElementById("edit-map-height-0-value").value + "px";
        document.getElementById("openlayers-map").data.updateSize();
    }
    var coords = document.getElementById("edit-center-0-value").value.split(",");
    if(coords.length === 2) {
        map_.getView().setCenter([parseFloat(coords[0]),parseFloat(coords[1])]);
    } 
    map_.getView().setZoom(document.getElementById("edit-zoom-0-value").value);
    
    function onMoveEnd(evt){
        var map = evt.map;
        var center = map.getView().getCenter();
        var zoom = map.getView().getZoom();
        document.getElementById("edit-center-0-value").value = center;
        document.getElementById("edit-zoom-0-value").value = zoom;
    };
    map_.on('moveend', onMoveEnd);    
}
