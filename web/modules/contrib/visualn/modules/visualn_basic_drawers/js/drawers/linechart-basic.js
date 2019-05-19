(function ($, Drupal, c3) {
  Drupal.visualnData.drawers.visualnLinechartBasicDrawer = function(drawings, vuid) {
    var drawing = drawings[vuid];
    var html_selector = drawing.html_selector;
    //$('.' + html_selector).append('<div width="960" height="500">');
    var data = drawing.resource.data;
    var linechart_selector = '.' + html_selector;

    // @todo: check if data exists for all series
    //   if not, use blank values or just do not show the series
    var series_number = drawing.drawer.config.series_number;
    var series_labels = drawing.drawer.config.series_labels;

    // add labels for the first value of each column
    var columns = { x: ['x'] };
    var i;
    for (i = 1; i <= series_number; i++) {
      columns['data' + i] = [series_labels[i]];
    }

    // add data for each column
    data.forEach(function(row){
      columns.x.push(row['x']);
      for (i = 1; i <= series_number; i++) {
        columns['data' + i].push(row['data'+i]);
      }
    });

    // convert data columns object into array
    var data_columns = [columns.x];
    for (i = 1; i <= series_number; i++) {
      data_columns.push(columns['data'+i]);
    }

    // @see https://c3js.org/samples/simple_xy.html
    var chart = c3.generate({
      bindto: linechart_selector,
      data: {
        x: 'x',
        columns: data_columns
/*
        columns: [
          ['x', 30, 50, 100, 230, 300, 310],
          ['data1', 30, 200, 100, 400, 150, 250],
          ['data2', 130, 300, 200, 300, 250, 450]
        ]
*/
      }
    });
  };

})(jQuery, Drupal, c3);
