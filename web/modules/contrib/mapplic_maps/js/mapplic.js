Drupal.behaviors.InitializeMapplic = {
  attach: function (context, settings) {
  	console.log(drupalSettings.mapplic);
    var mapplic = jQuery("#mapplic").mapplic({
    	source: '/mapplic/json',
			sidebar: drupalSettings.mapplic.sidebar,
      mapfill: drupalSettings.mapplic.mapfill,
      zoombuttons: drupalSettings.mapplic.zoombuttons,
      clearbutton: drupalSettings.mapplic.clearbutton,
      minimap: drupalSettings.mapplic.minimap,
      locations: drupalSettings.mapplic.locations,
      fullscreen: drupalSettings.mapplic.fullscreen,
      hovertip: drupalSettings.mapplic.hovertip,
      search: drupalSettings.mapplic.search,
      animate: drupalSettings.mapplic.animate,
      developer: drupalSettings.mapplic.developer,
      zoom: drupalSettings.mapplic.zoom,
      lightbox: false,
      maxscale: drupalSettings.mapplic.maxscale,
    });
  }
};
