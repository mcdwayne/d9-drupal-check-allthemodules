/**
 * @file
 * Author: Synapse-studio.
 */
(function ($) {
  'use strict';

  const YAPI = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
  const W_H = window.innerHeight;
  var mapData;
  var mapCenterLat;
  var mapCenterLng;
  var init = false;

  $(document).ready(function () {
    prepareMap();
  });

  function prepareMap() {
    var pos = '<div id="synmap"></div>';
    mapData = drupalSettings.synmapReplace ? drupalSettings.synmapReplace : drupalSettings.synmap;
    if (!$(mapData.map.attach).length) {
      return false;
    }
    mapCenterLat = mapData.map.offsetX ? parseFloat(mapData.map.latitude) + parseFloat(mapData.map.offsetX) : mapData.map.latitude;
    mapCenterLng = mapData.map.offsetY ? parseFloat(mapData.map.longitude) + parseFloat(mapData.map.offsetY) : mapData.map.longitude;
    $(mapData.map.attach).before(pos);
    loadMap();
  }

  function loadScript() {
    let script = document.createElement('script');
    script.onload = function() {
      ymaps.ready(ymapsInit);
    };
    script.src = YAPI;
    document.getElementsByTagName('body')[0].appendChild(script);
  }

  function loadMap() {
    let map = document.getElementById('synmap');

    checkScroll();

    window.addEventListener('scroll', function (e) {
      checkScroll();
    });

    function checkScroll() {
      let condition = window.pageYOffset - (map.offsetTop - 2 * W_H);
      if (condition > 0 && !init) {
        loadScript();
        init = true;
      }
    }
  }

  function ymapsInit() {
    let map = new ymaps.Map('synmap', {
      center: [mapCenterLng, mapCenterLat],
      zoom: mapData.map.zoom,
      controls: ['zoomControl', 'fullscreenControl']
    });
    map.behaviors.disable('scrollZoom');

    let iconShape = {
      type: 'Rectangle',
      coordinates: [
        [0, 0],
        [41, 60]
      ]
    };
    let iconOffset = [-20, -56];
    let layout = ymaps.templateLayoutFactory.createClass(`
      <svg class="synmap-point" viewBox="0 0 42 60" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M21 0C7.31177 0 0.54541 9.54 0.54541 21.3082C0.54541 33.0764 21 60 21 60C21 60 41.4545 33.0764 41.4545 21.3082C41.4545 9.54 34.6881 0 21 0ZM21 28.6364C14.9754 28.6364 10.0909 23.7518 10.0909 17.7273C10.0909 11.7027 14.9754 6.81818 21 6.81818C27.0245 6.81818 31.909 11.7027 31.909 17.7273C31.909 23.7518 27.0245 28.6364 21 28.6364Z"/>
      </svg>
    `);
    var pointsCollection = new ymaps.GeoObjectCollection();

    $.each(mapData.data, function (index, POINT) {
      let pointLayout = {
        iconLayout: layout,
        iconContent: 'Active',
        iconShape: iconShape,
        iconOffset: iconOffset,
      };

      let Placemark = new ymaps.Placemark([POINT.longitude, POINT.latitude], {
        balloonContentHeader: mapData.data.contact.name,
      }, pointLayout);
      pointsCollection.add(Placemark)
    });
    map.geoObjects.add(pointsCollection);

    // Center Position.
    if (mapData.map.centerAuto === true) {
      var bounds = map.geoObjects.getBounds();
      map.setBounds(bounds, {
        checkZoomRange: true
      }).then(function () {
        if (map.getZoom() > 16) {
          map.setZoom(16);
        }
      });
    }
    else {
      // Move center.
      var position = map.getGlobalPixelCenter();
      map.setGlobalPixelCenter([
        position[0] - mapData.map.centerAutoX,
        position[1] - mapData.map.centerAutoY
      ]);
    }
  }
})(this.jQuery);
