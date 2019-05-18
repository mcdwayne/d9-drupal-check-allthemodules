console.log("DRAW");
ol.customcontrol.Draw = function(opt_options, settings) {
    console.log(opt_options);
    console.log(settings);
    console.log('Draw');
    var options = opt_options || {};

    var button = document.createElement('button');
    button.innerHTML = 'D';

    var this_ = this;
    var map = this_.getMap();

};
ol.inherits(ol.customcontrol.Draw, ol.control.Interaction);


