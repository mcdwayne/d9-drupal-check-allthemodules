(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.acobot = {
    attach: function (context, settings) {
    	console.log(drupalSettings.acobot.src);
    	$('#acobot-script').attr('src',drupalSettings.acobot.src);
    	var code = "";
      var script = document.createElement("script");
      script.setAttribute("src", drupalSettings.acobot.src);
      script.appendChild(document.createTextNode(code));
      document.body.appendChild(script);
      var _aco = _aco || [];
      _aco.push(['email', drupalSettings.acobot.email]);      
    }
  };

})(jQuery, Drupal, drupalSettings);