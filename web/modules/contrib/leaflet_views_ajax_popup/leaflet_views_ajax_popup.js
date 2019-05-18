(function ($) {

  $(document).on('leaflet.map', function (e, settings, lMap) {
    lMap.on('popupopen', function (e) {
      var content = $('.leaflet-ajax-popup', e.popup._contentNode);

      if (content.length) {
        var path = 'leaflet-views-ajax-popup';
        path += '/' + content.data('type');
        path += '/' + content.data('id');
        path += '/' + content.data('mode');
        $.get(Drupal.url(path), function (response) {
          if (response) {
            e.popup.setContent(response)
          }
        });
      }
    });
  });

})(jQuery);
