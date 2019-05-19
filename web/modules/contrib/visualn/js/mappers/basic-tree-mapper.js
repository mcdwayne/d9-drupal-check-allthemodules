(function ($, Drupal, d3) {
  Drupal.visualnData.mappers.visualnBasicTreeMapper = function(drawings, vuid) {

    var drawing = drawings[vuid];
    var data = drawing.resource.data;
    //var dataKeysMap = drawing.mapper.dataKeysMap;
    var dataKeysStructure = drawing.mapper.dataKeysStructure;

    function createFunction(dataKeysStructure, prefix) {
      var str = '';
      $.each(dataKeysStructure.structure, function(key, value) {
        // generally this will be always zero if isArray() is true
        // @todo: check if this works in case structure keys are numeric by design
        if (Array.isArray(value.structure) && value.structure.length == 0) {
          if (value.typeFunc != '') {
            str += prefix + '.' + key + ' = ' + value.typeFunc + '(o.' + value.mapping + '); '
          }
          else {
            str += prefix + '.' + key + ' = o.' + value.mapping + '; '
          }
        }
        else {
          str += prefix + '.' + key + ' = {}; ';
          str += createFunction(value, prefix + '.' + key);
        }
      });

      return str;
    }
    var str = createFunction({ structure: dataKeysStructure }, 'dataLine');
    str = 'var dataLine = {}; ' + str + 'return dataLine;';

    var mapLine = new Function('o', str);

    var newData = [];
    data.forEach( function (o) {
      //var a = { State: o.State, freq: { low: parseInt(o.low), mid: parseInt(o.mid), high: parseInt(o.high) } };
      var a = mapLine(o);
      newData.push(a);
    });
    console.log(newData);

    // @todo: this line doesn't work
    data = newData;
    drawing.resource.data = newData;
  };
})(jQuery, Drupal, d3);
