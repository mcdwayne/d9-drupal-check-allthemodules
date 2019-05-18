(function ($, Drupal, drupalSettings) {
  'use strict';

  // Set all the variables from settings
  let svg_url = drupalSettings.amap.svg_url;

  const svg_url_path = drupalSettings.amap.svg_url_path;
  const svg_eid_mn = drupalSettings.amap.svg_eid_mn;
  const svg_eid_class_mn = drupalSettings.amap.svg_eid_class_mn;
  const svg_eid_style_mn = drupalSettings.amap.svg_eid_style_mn;
  const svg_eid_url_mn = drupalSettings.amap.svg_eid_url_mn;

  // Get all path if a URL Path # > 0 is provided
  let paramPath = '';

  if (svg_url_path > 0) {

    let pathArray = $(location).attr('pathname').split('/');
    let arrayLength = pathArray.length;

    for (let i = 0; i < arrayLength; i++) {
      if (i > svg_url_path - 1) {
        paramPath = paramPath + '/' + pathArray[i];
      }
    }
    svg_url = svg_url + paramPath;

  }

  Drupal.behaviors.amap = {
    attach: function (context, settings) {
      /**
       * Implements ajax block behaviour.
       */
      $.ajax({
        url: svg_url,
        method: 'GET',
        success: function(data, status, xhr) {

          for(let i = 0; i < data.length; i++) {
            let obj = data[i];

            // Add class if available
            if (svg_eid_class_mn !== '') {
              $('#' + obj[svg_eid_mn])
                .addClass(obj[svg_eid_class_mn]);
              $('#' + obj[svg_eid_mn] + '_Label')
                .addClass(obj[svg_eid_class_mn]);
            }

            // Add style if available
            if (svg_eid_style_mn !== '') {
              $('#' + obj[svg_eid_mn])
                .css({fill: obj[svg_eid_style_mn]});
              $('#' + obj[svg_eid_mn] + '_Label')
                .css({fill: obj[svg_eid_style_mn]});
            }

            // Add URL for item if available
            if (svg_eid_url_mn !== '') {
              $('#' + obj[svg_eid_mn])
                .css( 'cursor', 'pointer' )
                .click(function (e) {
                  e.preventDefault();
                  window.location = obj[svg_eid_url_mn];
                });
              $('#' + obj[svg_eid_mn] +'_Label')
                .css( 'cursor', 'pointer' )
                .click(function (e) {
                  e.preventDefault();
                  window.location = obj[svg_eid_url_mn];
                });
              $('#' + obj[svg_eid_mn] +'_Text')
                .css( 'cursor', 'pointer' )
                .click(function (e) {
                  e.preventDefault();
                  window.location = obj[svg_eid_url_mn];
                });
            }
          }

        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
