/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function ($, Drupal, window, document, undefined) {

var material_colours = {
  'red':          '#f44336',
  'pink':         '#e91e63',
  'purple':       '#9c27b0',
  'deep_purple':  '#673ab7',
  'indigo':       '#3f51b5',
  'blue':         '#2196f3',
  'light_blue':   '#03a9f4',
  'cyan':         '#00bcd4',
  'teal':         '#009688',
  'green':        '#4caf50',
  'light_green':  '#8bc34a',
  'lime':         '#cddc39',
  'yellow':       '#ffeb3b',
  'amber':        '#ffc107',
  'orange':       '#ff9800',
  'deep_orange':  '#ff5722',
  'brown':        '#795548',
  'blue_grey':    '#607d8b'
};

// To understand behaviors, see https://drupal.org/node/756722#behaviors
Drupal.behaviors.booking_stats_page = {
  attach: function(context, settings) {
    // console.log('context: ', context);
    // console.log('settings: ', settings);
    // console.log('drupalSettings: ', drupalSettings);

    Drupal.booking_stats_page.build_charts(context, settings);

    var bookable = getParameterByName('bookable');
    $('select[name="bookable_filter"]').val(bookable);

    var URL = location.protocol + '//' + location.host + location.pathname;

    $('select[name="bookable_filter"]').change(function() {
      // console.log('URL: ', URL);
      var new_url = URL + '?bookable=' + $(this).val();
      // console.log('new_url: ', new_url);
      window.location.href = new_url;
    });

  }
};



Drupal.booking_stats_page = {

  build_charts: function(context, settings) {
    // console.log('build_charts()');
    var chart_container = $("#bookingCharts", context);

    var charts = [
      'bookings_count',
      'occupancy_perc',
      'revenue'
    ];

    $.each(charts, function(key, data_name) {

      var chart_data = drupalSettings['booking_data'][data_name];

      chart_container
        .append('<h2>' + chart_data.title + '</h2>')
        .append('<div class="chart" id="' + data_name + '"></div>');

      var chart_context = $('<canvas width="400" height="400"></canvas>')
        .appendTo(chart_container.find('.chart#' + data_name));
      // console.log('chart_context: ', chart_context);

      var myChart = new Chart(chart_context, {
        type: 'bar',
        data: {
          labels: Object.keys(chart_data['data']),
          datasets: [{
            label: chart_data.series_label,
            data: Object.values(chart_data['data']),
            backgroundColor: material_colours[chart_data.colour],
            // borderColor: 'rgba(255,99,132,1)',
            borderWidth: 1
          }]
        },
        options: {
          maintainAspectRatio: false,
          scales: {
            yAxes: [{
              ticks: {
                beginAtZero:true
              }
            }]
          }
        }
      });

    });

  }

}

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}



})(jQuery, Drupal, this, this.document);
