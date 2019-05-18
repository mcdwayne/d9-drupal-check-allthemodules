ol.customcontrol.ZoomIn = function(opt_options, settings) {
    console.log(opt_options);
    console.log(settings);
    var options = opt_options || {};

    var button = document.createElement('button');
    button.innerHTML = '+';

    var this_ = this;
    var zoomIn = function() {
      this_.getMap().getView().setZoom(this_.getMap().getView().getZoom() + 0.5);  
    };

    button.addEventListener('click', zoomIn, false);
    button.addEventListener('touchstart', zoomIn, false);

    var element = document.createElement('div');
    element.className = 'ol-unselectable ol-control';
    element.appendChild(button);

    ol.control.Control.call(this, {
      element: element,
      target: options.target
    });

};
ol.inherits(ol.customcontrol.ZoomIn, ol.control.Control);


