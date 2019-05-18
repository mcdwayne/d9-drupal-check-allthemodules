(function ($) {
    /**
     * @type {{attach: Drupal.behaviors.googleGeochart.attach}}
     */
    Drupal.behaviors.googleGeochart = {
        attach: function (context, settings) {

            var googleMapApiKey = drupalSettings.google.geochart.mapsApiKey;
            google.charts.load('current', {
                'packages':['geochart'],
                mapsApiKey: "'"+ googleMapApiKey + "'"
            });
            google.charts.setOnLoadCallback(drawRegionsMap);

            var dataCountry = drupalSettings.google.geochart.country;

            function drawRegionsMap() {
                var data = google.visualization.arrayToDataTable(dataCountry);

                var options = {};

                var chart = new google.visualization.GeoChart(document.getElementById('regions_div'));

                chart.draw(data, options);
            }
        }
    };
})(jQuery);