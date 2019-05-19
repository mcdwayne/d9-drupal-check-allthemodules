// @todo: this code is completely based on js/adapters/remote-dsv-to-js-array-adapter.js
//   so see todos list there
(function ($, Drupal, d3, xml2json) {
  Drupal.visualnData.adapters.visualnRemoteXmlToJSArrayAdapter = function(drawings, vuid, managerCallback) {
    var fileType = drawings[vuid].adapter.fileType;

    if (typeof(fileType) == 'undefined' || fileType == '') {
      // @todo: set a warning and return empty data (or false or some kind of result code)
    }
    else {
      switch (fileType) {
        // @todo: currently this adapter should always load xml2json library
        //    even if it is not used (e.g. when csv is processed)
        case 'xml' :
          //mimeType = 'text/xml';
          var sourceD3 = d3.xml(drawings[vuid].adapter.fileUrl, function(error, data) {
            // xml parsing returns an xml object
            var jsonData = xml2json(data, "");
            jsonData = JSON.parse(jsonData);
            // @todo: wrapper can be other than "element". maybe make adapter configurable
            data = jsonData['root']['element'];
            // pass data to manager callback when request successfully finished
            managerCallback(data);
          });
          break;
      }
    }
  };

})(jQuery, Drupal, d3, xml2json);


