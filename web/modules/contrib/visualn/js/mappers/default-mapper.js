// @todo: maybe rename the file (to comply library name) or the library itself
(function ($, Drupal, d3) {
  Drupal.visualnData.mappers.visualnDefaultMapper = function(drawings, vuid) {

    var drawing = drawings[vuid];
    var data = drawing.resource.data;

    // @todo: Array.prototype.filter() can be used instead

    // @todo: return if data is empty


    // @todo: check if needs remapping and remap if true
    // if those that are not empty, have the same key and value, there is no need in remapping
    // or if a special flag is set by adapter (or even a drawer), then don't do remapping also
    // also a flag can be set by the mapper itself if there was a chance to remap values while
    // adapter processing

    // @todo: keysMap must containt all keys even if no mapping is provided for some of them,
    //   see remapping code below
    var keysMap = drawing.mapper.dataKeysMap;

    var count = 0;
    var key;
    var newKeysMap = {};

    // get new keysMap with only non-empty values
    for (key in keysMap) {
      if (keysMap.hasOwnProperty(key)) {
        if (keysMap[key] != '' && keysMap[key] != key) {
          newKeysMap[key] = keysMap[key];
          count++;
        }
      }
    }

    // convert keys map into array to avoid checking hasOwnProperty() for every row
    // @todo: check for a best practice
    var keysMapArr = [];
    for (key in keysMap) {
      if (keysMap.hasOwnProperty(key)) {
        keysMapArr.push({ newKey: key, dataKey: keysMap[key] });

        // add a flag for the case when remapping is really needed
        if (keysMap[key] != '' && keysMap[key] != key) {
          count++;
        }

      }
    }

    // @todo: it is also possible to generate function code here (see basic-tree-mapper.js)

    // add mapping functionality (replace data keys)
    if (count) {
      // @todo:
      // foreach row in data replace keys
      // if a key already exists but it is used in remapping for another key (which is not recommeded),
      // create temporary value for that key

      //console.log(newKeysMap);
      data.forEach( function (o, index, arr) {
        // @todo: use temporary object to keep all data of the current row

        // create a new object with remapped data keys and use it instead the original one
        var currentRow = {};
        keysMapArr.forEach(function(keyMapping) {
          currentRow[keyMapping.newKey] = o[keyMapping.dataKey];
        });
        arr[index] = currentRow;

        // @todo: delete unused objects




/*
        for (key in newKeysMap) {
          if (newKeysMap.hasOwnProperty(key)) {
            var oldKey = newKeysMap[key];
            var newKey = key;
            // http://stackoverflow.com/questions/4647817/javascript-object-rename-key
            if (oldKey !== newKey) {
              Object.defineProperty(o, newKey,
                Object.getOwnPropertyDescriptor(o, oldKey));
              // @todo: this doesn't work in case multiple data keys are using the same data column
              //   the key should be removed after all new keys are checked, fix it
              //   to leave it also is not correct, only used (required) keys should be left
              //delete o[oldKey];
            }
          }
        }
*/


      });
    }
    //console.log(data);

    // @todo: since drawers execute after mappers, there should be a way to set that special flag
    // to avoid remapping (or just explicitly disable mapper somewhere else before page rendering,
    // e.g. in manager, or in drawer prepareBuild() method)

  };
})(jQuery, Drupal, d3);

