(function ($, Drupal, d3) {
  Drupal.visualnData.adapters.visualnDataArrayToJSArrayAdapter = function(drawings, vuid, managerCallback) {
    var drawing = drawings[vuid];
    var html_selector = drawing.html_selector;

    var data = drawings[vuid].adapter.adapterData;

    managerCallback(data);

    return data;
  };

})(jQuery, Drupal, d3);
