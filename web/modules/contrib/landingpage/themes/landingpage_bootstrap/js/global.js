(function ($, Drupal) {
var menu_top = $('.sticky_top:first');  
var offset_top = menu_top.offset();
var menu_bottom = $('.sticky_bottom:first');  
var offset_bottom = menu_bottom.offset();
  $(window).scroll(function() {
    if (menu_top.length) {
      if ($(this).scrollTop() > offset_top.top){ 
        $('.p-sticky.sticky_top:first').show(); 
      }
      else{
        $('.p-sticky.sticky_top:first').hide();
      }
    }
    if (menu_bottom.length) {    
      if ($(this).scrollTop() < (offset_bottom.top - $(this).height() + menu_bottom.height())){ 
        $('.p-sticky.sticky_bottom:first').show(); 
      }
      else{
        $('.p-sticky.sticky_bottom:first').hide();
      } 
    }   
  });

    $('a[href*="#"]:not([href="#"])').click(function() {
      if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
        var target = $(this.hash);
        target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
        if (target.length) {
          $('html, body').animate({
            scrollTop: target.offset().top
          }, 1000);
          return false;
        }
      }
    });

  Drupal.behaviors.landingpage_google_map = {
    attach: function (context) {

      $('.google-map-field .map-container').once('.google-map-field-processed').each(function(index, item) {

        // Get the settings for the map from the Drupal.settings object.
        var lat = $(this).attr('data-lat');
        var lon = $(this).attr('data-lon');
        var zoom = parseInt($(this).attr('data-zoom'));
        var type = $(this).attr('data-type');
        var show_marker = $(this).attr('data-marker-show') === "true";
        var show_controls = $(this).attr('data-controls-show') === "true";

        // Create the map coords and map options.
        var latlng = new google.maps.LatLng(lat, lon);
        var mapOptions = {
          zoom: zoom,
          center: latlng,
          streetViewControl: false,
          scrollwheel: false,
          mapTypeId: type,
          disableDefaultUI: show_controls ? false : true,
        };
        google_map_field_map = new google.maps.Map(this, mapOptions);

        google.maps.event.trigger(google_map_field_map, 'resize')

        // Drop a marker at the specified position.
        marker = new google.maps.Marker({
          position: latlng,
          optimized: false,
          visible: show_marker,
          map: google_map_field_map
        });

      });

    }
  }

  //Drupal.behaviors.landingpage_carousel = {
  //  attach: function (context) {
  //    $('#landingpage-carousel').carousel();
  //  }
  //}  

})(jQuery, Drupal);
