ol.customcontrol.Rotate = function(opt_options, settings) {
    console.log(opt_options);
    console.log(settings);
    var options = opt_options || {};

    var button = document.createElement('button');
    button.innerHTML = 'N';

    var this_ = this;
    var handleRotateNorth = function() {
      console.log(this_.getMap().getView().getRotation());
      if(this_.getMap().getView().getRotation() !== 0) {
        this_.getMap().getView().setRotation( 0 );
      } else {
        this_.getMap().getView().setRotation(this_.getMap().getView().getRotation() + 45);
      }
      
    };

    button.addEventListener('click', handleRotateNorth, false);
    button.addEventListener('touchstart', handleRotateNorth, false);

    var element = document.createElement('div');
    element.className = 'ol-unselectable ol-control';
    element.appendChild(button);

    ol.control.Control.call(this, {
      element: element,
      target: options.target
    });

};
ol.inherits(ol.customcontrol.Rotate, ol.control.Control);


