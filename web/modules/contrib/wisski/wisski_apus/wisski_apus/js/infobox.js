/**
*
* @author Martin Scholz, WissKI project
*/


(function($, window, Drupal, drupalSettings, undefined) {

  "use strict";

  Drupal.behaviors.wisskiInfobox = {
    
    attach: function (context, settings) {
      
//      $(window.document).once('wisskiApusInfobox').each(function() {
      $(context)/*.once('wisskiApusInfobox')*/.each(function() {
        
        $(this).find('[data-wisski-anno-id], [data-wisski-anno]').each(function() {
        $(this).tooltip({
          items: '[data-wisski-anno-id], [data-wisski-anno]',
          close: function(event, ui) {
            console.log("close");
            event.preventDefault();
            return false;
          },
          content: function (callback) {
            var $element = $(this);
            
            var anno = Drupal.wisskiApus.parseAnnotation(this, context);

            if (!anno) {

              return '<p>No information available.<br/>This annotation seems to be corrupt.</p>';
            
            } else {
              
              if (!!anno.body) {
                // We delete the body as it contains the $elements property
                // which is not serializable. The server doesn't need the body
                // information anyway.
                delete(anno.body);
              }
              // prepare ajax request settings
              var ajaxSettings = {
                // TODO: we append a random number as server caches too
                // aggressively otherwise
                url: drupalSettings.wisskiApus.infobox.contentCallbackURL + "/" + Math.floor(Math.random() * 10000000),
                data: { 
                  anno: anno
                },
                dataType: 'html', // we expect html from the server
              }
              // start the request
              var xhr = $.ajax(ajaxSettings)
                .done(function (data, status, jqXHR) {
                  callback(data);
                })
                .fail(function (jqxhr, status, error) {
                  var errorMsg = "An error occurred while fetching data: " + error;
                  callback(errorMsg);
                });
              
              return '<p class="wait throbber">Please wait ...</p>';

            }

          }

        });
        });

      });

    }
    
  };

})(jQuery, window, Drupal, drupalSettings);

