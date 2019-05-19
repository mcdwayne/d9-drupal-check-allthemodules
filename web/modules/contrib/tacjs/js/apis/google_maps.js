(function (drupalSettings) {
tarteaucitron.user.googlemapsKey = drupalSettings.GoogleMaps.value;
(tarteaucitron.job = tarteaucitron.job || []).push('googlemaps');
})(drupalSettings);