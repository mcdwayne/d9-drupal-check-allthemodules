(function (jq, Drupal, drupalSettings) {
  Drupal.behaviors.wisski_mirador_Behavior = {
    attach: function (context, settings) {
//      alert($.fn.jquery);
      jq('div#viewer', context).once('wisski_mirador').each(function () {
//        alert($.fn.jquery);
//        alert(jQuery19.fn.jquery);
//        (function($, jQuery) {
//          alert(jQuery.fn.jquery);

//          console.log('yay', drupalSettings.wisski.mirador.data);          
        
          jq(function() {
            jQuery = jQuery19;
            $ = jQuery19;          
//            alert(jQuery.fn.jquery);
            Mirador({
              id: "viewer",
              buildPath: "/libraries/mirador/",
              layout: drupalSettings.wisski.mirador.layout,
              data:  drupalSettings.wisski.mirador.data,
              "windowObjects" : drupalSettings.wisski.mirador.windowObjects
            });
          });
          jQuery.noConflict(true);
//          alert(jQuery.fn.jquery);
          
//        })(jQuery19, jQuery19);
                
//        alert(jQuery.fn.jquery);
//        alert($.fn.jquery);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);