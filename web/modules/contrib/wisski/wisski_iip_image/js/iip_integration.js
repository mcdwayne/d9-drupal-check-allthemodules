(function ($, Drupal, drupalSettings) {

//console.log('bbb', drupalSettings.wisski.iip.config);
var iipConfig = $.extend({
  server: "/fcgi-bin/iipsrv.fcgi",
//  credit: credit,
// drupalSettings.path.baseUrl
  prefix: drupalSettings.path.baseUrl + 'libraries/iipmooviewer/images/',
  showNavWindow: true,
  showNavButtons: true,
  winResize: true,
  protocol: 'iip',
}, drupalSettings.wisski.iip.config);

$(document).bind('cbox_complete', function() {

  jQuery = jQuery29;
  $ = jQuery29;

  iipConfig.image = [jQuery.colorbox.element().attr("iip")];


//  var credit = '&copy; <a href="http://www.gnm.de/">Germanisches Nationalmuseum</a>';

  var iipmooviewer = new IIPMooViewer( "cboxLoadedContent", iipConfig);

  jQuery.colorbox.resize({width: 1000, height: 600});

  jQuery.noConflict(true);
  
});

Drupal.behaviors.iip_integration_Behavior = {
    attach: function (context, settings) {
//       alert("yay!");
//      $(context).find('input.iipIntegrationBehaviour').once('iipIntegrationBehaviour').each(function () {
      $(context).once('iipIntegrationBehaviour').each(function () {
//        alert("yay!1");

        iipConfig.image = [$('.wisski-inline-iip').attr("iip")];

        if($('.wisski-inline-iip').attr('wisski-inline-iip')) {
//          alert(drupalSettings.path.baseUrl);
          var prefix = drupalSettings.path.baseUrl + 'libraries/iipmooviewer/images/';

//  var credit = '&copy; <a href="http://www.gnm.de/">Germanisches Nationalmuseum</a>'

//console.log('aaa', iipConfig);

          var iipmooviewer = new IIPMooViewer( "wisski-iip-cont", iipConfig);
        }
        
//        jQuery.colorbox.resize({width: 1000, height: 600});
      });

    }

  };

})(jQuery, Drupal, drupalSettings);
