(function ($) {
  "use strict";
  jQuery(document).ready(function(){
   // alert("safdsf");
    var Page = (function() {
          var $navArrows = jQuery( '#nav-arrows' ).hide(),
          $shadow = jQuery( '#shadow' ).hide(),
            slicebox = jQuery( '#sb-slider' ).slicebox( {
              onReady : function() {
                $navArrows.show();
                $shadow.show();
              },
              orientation : 'r',
              cuboidsRandom : true,
              disperseFactor : 30
            } ),
       
            init = function() {
              initEvents();              
            },
            initEvents = function() {

              // add navigation events
              $navArrows.children( ':first' ).on( 'click', function() {
                slicebox.next();
                return false;

              } );

              $navArrows.children( ':last' ).on( 'click', function() {
                slicebox.previous();
                return false;

              } );

            };
            return { init : init };

        })();
        Page.init();
    });
})(jQuery);
