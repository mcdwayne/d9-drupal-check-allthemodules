(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.proximity_search_autocomplete = {
    attach: function (context, settings) {
      var $requestZip;
        jQuery('#edit-field-location-postal-code').keyup(function() {
        var term = jQuery('#edit-field-location-postal-code').val();
        if($requestZip != null) {
               $requestZip.abort();
               $requestZip = null;
        }
        console.log(term);
        $requestZip = jQuery.ajax({
          dataType: "json",
          url: drupalSettings.path.baseUrl + "getlnt" ,
          data: { q: term },
          success: function (data) {
            console.log(data);
                if(data.length > 0) {
                var content1 =  data['lat'];
                var content2 = data['long'];
              } else {
                    var content1 = "";
                    var content2 = "";
              }
              jQuery("#edit-field-current-coordinates-proximity-source-configuration-origin-lat").val(data.lat);
              jQuery("#edit-field-current-coordinates-proximity-source-configuration-origin-lon").val(data.long);
            }
          });
          return false;
      });
    }};
})
jQuery, Drupal, drupalSettings);
