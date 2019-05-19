// @todo: use js Promises feature
(function ($, Drupal) {

  // @todo: move contents into an init function
  window.addEventListener('visualnCoreProcessed', function (e) {
    //console.log(JSON.stringify(Drupal.visualnData));


    // @todo: consider implementing updating events and
    // behaviours (e.g. depending on interactions or new data available in the source/resource)

    // Register all drawings which want to be managed by the builder
    // the settings about the graphs are provided by views style or field formatter (or other).
    // Then for each one the correponding Drawer is asked to register and provide info about its
    // dependencies and requirements (Mappers, Adapters, data sources).
    // Then each of them is processed in order.
    // A Drawer can do everything on it's own without registering to builder
    // or even use a custom builder with its custom arbitrary logic.

    var settings = e.detail;

    // @todo: object to array conversion would better be done before sending settings to browser
    var handlerItems = settings.visualn.handlerItems.builders.visualnDefaultBuilder;
    handlerItems = Object.keys(handlerItems).map(function (key) { return handlerItems[key]; });
    settings.visualn.handlerItems.builders.visualnDefaultBuilder = handlerItems;
    // process all drawings, managed by the builder
    $(settings.visualn.handlerItems.builders.visualnDefaultBuilder).each(function(index, vuid){

      var drawing = Drupal.visualnData.drawings[vuid];
      var html_selector = drawing.html_selector;
      $('.' + html_selector).once('visualnDrawingInit').each(function() {

        // drawing.drawer is considered to be always set, since there is no need to have a drawing w/o a drawer
        var drawing = Drupal.visualnData.drawings[vuid];
        // @todo: this is temporary solution to exclude drawers that don't use js,
        //    actually there should be no settings at all at clientside for such drawers
        if (typeof drawing.drawer == 'undefined') {
          return;
        }
        var drawerId = drawing.drawer.drawerId;
        // check if drawerId exists
        if (drawerId == '') {
          return;
        }


        if (typeof drawing.adapter != 'undefined') {
          var adapterId = drawing.adapter.adapterId;
          // @todo: maybe pass just a drawing or also a drawing
          // @todo: pass also a callback to run when adapter result is ready (e.g. for requesting urls)
          // @todo: use js Promises feature
          var callback = function(data){

            // @todo: adapters etc. should operate not on data directly but on resource object
            //   and return resource object (variable) as well

            var resource = {};
            resource.data = data;

            drawing.resource = resource;


            // apply mapper if any
            if (typeof drawing.mapper != 'undefined') {
              var mapperId = drawing.mapper.mapperId;
              Drupal.visualnData.mappers[mapperId](Drupal.visualnData.drawings, vuid);
            }

            // draw final drawing
            Drupal.visualnData.drawers[drawerId](Drupal.visualnData.drawings, vuid);
          };
          // @todo: in some cases we need to pass row conversion function (see https://github.com/d3/d3-request#tsv)
          //   which depends on a given drawer. so maybe give drawer a chance to make some tuning on adapter before request.
          //   But in this case adapter and drawer should use the same library (d3.js in our case).
          Drupal.visualnData.adapters[adapterId](Drupal.visualnData.drawings, vuid, callback);
        }
        else {
          // consider the case when mapper is used without adapter
          // apply mapper if any
          if (typeof drawing.mapper != 'undefined') {
            var mapperId = drawing.mapper.mapperId;
            Drupal.visualnData.mappers[mapperId](Drupal.visualnData.drawings, vuid);
          }

          Drupal.visualnData.drawers[drawerId](Drupal.visualnData.drawings, vuid);
        }

      });
    });

  });

})(jQuery, Drupal);

