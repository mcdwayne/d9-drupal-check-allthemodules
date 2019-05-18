/**
 * @file
 * Provides the Google Analytics Reports using line Chart
 * and associated methods for week, month and year.
 */

//Function to make line chart on report page
var chart = AmCharts.makeChart( "chartdiv3", {
  "type": "xy",
  "pathToImages": "https://www.amcharts.com/lib/3/images/",
  //"dataProvider": chartData,
  "dataLoader": {
        "url": "/analytics-light-report/user-view-week",
        "format": "json"
  },  
  "graphs": [ {
	//"balloonText": "[[day]]<br><b><span style='font-size:14px;'>value:[[user]]</span></b>",
    "bullet": "circle",
    "bulletSize": 8,
    "lineAlpha": 1,
    "lineThickness": 2,
    "fillAlphas": 0,
    "xField": "day",
    "yField": "user",
  } ],
      "valueAxes": [ {
        "id": "v1",
        "gridAlpha": 0.07,
        "title": "Users"
      }, {
        "id": "v2",
        "gridAlpha": 0,
        "position": "bottom",
        "title": "Date"
      } ],
  "legend": {
    "enabled": true
  }
} 
);
function set_data_set(dataset_url) {
  AmCharts.loadFile(dataset_url, {}, function(data) {
    chart.dataProvider = AmCharts.parseJSON(data);
    chart.validateData();
  });
}

