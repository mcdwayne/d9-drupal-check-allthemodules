(function ($) {

  function getLocation() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(showPosition);
    }
    else {
      alert ("Geolocation is not supported by this browser.");
    }
  }
  function showPosition(position) {
    document.cookie="weather_latitude=" + position.coords.latitude;
    document.cookie="weather_longitude=" + position.coords.longitude;
  }

  $(document).ready(function() {

    if (document.cookie == '') {

      getLocation();

      window.location.reload();
    }
  });
})(jQuery, Drupal, window);
