(function ($) {

  Drupal.behaviors.mapbox_gl = {
    attach: function (context, settings) {

      // Loop through each on the page.
      $.each(settings.mapboxGl, function (mapId, mapData) {
        if (!$('#' + mapData.options.container).hasClass('mapbox-gl-processed')) {
          $('#' + mapData.options.container).parent('.mapbox-gl-wrapper')
                  .css('height', mapData.config.height);

          var popupStyle = mapData.config.popup;

          mapboxgl.accessToken = mapData.accessToken;

          // Create the map.
          var map = new mapboxgl.Map(mapData.options);

          // Keep track of layers which can be toggled.
          var toggleableLayerIds = [];

          // Wait until map has loaded.
          map.on('load', function () {

            // Loop through sources.
            $.each(mapData.sources, function (sourceId, sourceData) {
              map.addSource(sourceId, sourceData);
            });

            // Loop through the layers.
            $.each(mapData.layers, function (index, layerData) {
              map.addLayer(layerData);

              // Add layer ID to toggle list.
              toggleableLayerIds[index] = layerData.id;
            });

            // Loop through the layers and add links to toggle them.
            $.each(toggleableLayerIds, function (index, data) {
              var id = toggleableLayerIds[index];

              var link = document.createElement('a');
              link.href = '#';
              link.className = 'active';
              link.textContent = id;

              link.onclick = function (e) {
                var clickedLayer = this.textContent;
                e.preventDefault();
                e.stopPropagation();

                var visibility = map.getLayoutProperty(clickedLayer, 'visibility');

                if (visibility === 'visible') {
                  map.setLayoutProperty(clickedLayer, 'visibility', 'none');
                  this.className = '';
                } else {
                  this.className = 'active';
                  map.setLayoutProperty(clickedLayer, 'visibility', 'visible');
                }
              };

              var layers = document.getElementById(mapData.options.container + '-menu');
              layers.appendChild(link);

              // Add popups.
              map.on('click', id, function (e) {
                // Build a table, display all properties.
                var propertiesHtml = '<table>';
                $.each(e.features[0].properties, function (propertyName, propertyData) {
                  propertiesHtml += '<tr><td><strong>' + propertyName + '</strong></td><td>' + propertyData + '</td></tr>';
                });
                propertiesHtml += '</table>';

                // Check for popup style.
                if (popupStyle === 'popup') {
                  new mapboxgl.Popup()
                          .setLngLat(e.features[0].geometry.coordinates)
                          .setHTML(propertiesHtml)
                          .addTo(map);
                }
                // Bind to a different container.
                else if (popupStyle) {
                  document.getElementById(popupStyle).innerHTML = propertiesHtml;
                }

              });

              // Change the cursor to a pointer when the mouse is over the layer.
              map.on('mouseenter', id, function () {
                map.getCanvas().style.cursor = 'pointer';
              });

              // Change it back to a pointer when it leaves.
              map.on('mouseleave', id, function () {
                map.getCanvas().style.cursor = '';
              });

            });

            $(document).trigger('mapbox-gl.map', [map, mapData, mapId]);
          }); // Map on load.

          // Add some controls.
          $.each(mapData.config.controls, function (controlType, controlOptions) {
            // NavigationControl options are added differently.
            if (controlType == 'NavigationControl') {
              map.addControl(new mapboxgl[controlType](), controlOptions);
            } else if (controlType == 'MapboxGeocoder') {
              map.addControl(new MapboxGeocoder(controlOptions));
            } else {
              map.addControl(new mapboxgl[controlType](controlOptions));
            }
          });
          $('#' + mapData.options.container).addClass('mapbox-gl-processed');
        }
      });

    }

  };
})(jQuery);
