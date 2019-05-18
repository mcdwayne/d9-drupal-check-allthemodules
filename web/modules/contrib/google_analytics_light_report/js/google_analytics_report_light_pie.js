/**
 * @file
 * Provides the Google Analytics Reports using pie Chart
 */

//function use to make pie chart in block
var chart = AmCharts.makeChart( "top_browser_pie_chart", {
  "type": "pie",
  "dataLoader": {
        "url": "/analytics-light-report/top-browser-view",
        "format": "json"
  },
  "theme": "light",
  "titles": [ {
    "text": "Top Browsers",
    "size": 16
  } ],
  "valueField": "value",
  "titleField": "name",
  "startEffect": "elastic",
  "startDuration": 2,
  "labelRadius": 15,
  "innerRadius": "50%",
  "depth3D": 10,
  "balloonText": "[[title]]<br><span style=\'font-size:14px\'><b>[[value]]</b> ([[percents]]%)</span>",
  "angle": 15,
  "legend": {
    "enabled": true
  }
} );