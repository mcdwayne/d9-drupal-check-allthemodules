(function($) {
  'use strict';
  Drupal.behaviors.easyzoom = {
    attach: function (context, settings) {
      var shadow_color = drupalSettings.elevate_tint_shadow_color;
      var window_position = drupalSettings.elevate_window_position;
      var window_width = drupalSettings.elevate_window_width;
      var window_height = drupalSettings.elevate_window_height;
      var lens_size = drupalSettings.elevate_lens_size;

      $('#elevate_zoom--basic_zoom').elevateZoom({
        zoomWindowPosition: parseInt(window_position),
        zoomWindowWidth: parseInt(window_width),
        zoomWindowHeight: parseInt(window_height)
      });

      $('#elevate_zoom--tint_zoom').elevateZoom({
        tint: true,
        tintColour: shadow_color,
        tintOpacity: 0.5,
        zoomWindowPosition: parseInt(window_position),
        zoomWindowWidth: parseInt(window_width),
        zoomWindowHeight: parseInt(window_height)
      });

      $('#elevate_zoom--mousewheel_zoom').elevateZoom({
        tint: true,
        tintColour: shadow_color,
        tintOpacity: 0.5,
        zoomWindowPosition: parseInt(window_position),
        zoomWindowWidth: parseInt(window_width),
        zoomWindowHeight: parseInt(window_height),
        scrollZoom: true
      });
 
      $('#elevate_zoom--lens_zoom').elevateZoom({
        zoomType: 'lens',
        lensShape: 'round',
        lensSize: parseInt(lens_size)
      });

      $('#elevate_zoom--inner_zoom').elevateZoom({
        zoomType: 'inner',
        cursor: 'crosshair'
      });

      // Gallery
      $('#elevate_zoom--mousewheel_zoom_gallery').elevateZoom({
        gallery: 'elevate_zoom--mousewheel_zoom_gallery_list',
        cursor: 'pointer',
        galleryActiveClass: 'active',
        imageCrossfade: true,
        tint: true,
        tintColour: shadow_color,
        tintOpacity: 0.5,
        scrollZoom: true,
        zoomWindowPosition: parseInt(window_position),
        zoomWindowWidth: parseInt(window_width),
        zoomWindowHeight: parseInt(window_height)
      });

      $('#elevate_zoom--basic_zoom_gallery').elevateZoom({
        gallery: 'elevate_zoom--basic_zoom_gallery_list',
        cursor: 'pointer',
        galleryActiveClass: 'active',
        imageCrossfade: true,
        zoomWindowPosition: parseInt(window_position),
        zoomWindowWidth: parseInt(window_width),
        zoomWindowHeight: parseInt(window_height)
      });

      $('#elevate_zoom--tint_zoom_gallery').elevateZoom({
        gallery: 'elevate_zoom--tint_zoom_gallery_list',
        cursor: 'pointer',
        galleryActiveClass: 'active',
        tint: true,
        tintColour: shadow_color,
        tintOpacity: 0.5,
        zoomWindowPosition: parseInt(window_position),
        zoomWindowWidth: parseInt(window_width),
        zoomWindowHeight: parseInt(window_height)
      });

      $('#elevate_zoom--inner_zoom_gallery').elevateZoom({
        gallery: 'elevate_zoom--inner_zoom_gallery_list',
        galleryActiveClass: 'active',
        zoomType: 'inner',
        cursor: 'crosshair'
      });

      $('#elevate_zoom--lens_zoom_gallery').elevateZoom({
        gallery: 'elevate_zoom--lens_zoom_gallery_list',
        cursor: 'pointer',
        galleryActiveClass: 'active',
        zoomType: 'lens',
        lensShape: 'round',
        lensSize: parseInt(lens_size)
      });
    }
  };

})(jQuery);
