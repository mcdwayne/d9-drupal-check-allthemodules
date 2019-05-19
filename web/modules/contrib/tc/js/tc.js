(function($) {

Drupal.behaviors.tc = {
  attach: function(context) {
    Chart.defaults.global.responsive = true;
    Chart.defaults.global.animation = false;
    Chart.defaults.global.showTooltips = false;
    $('.tcChart').each(function () {
      var field = $(this).attr('data-tcchart');
      $(this).append('<canvas id="tcCanvas' + field + '" width="640" height="480"></canvas>');
      var ctx = $('#tcCanvas' + field).get(0).getContext("2d");
      drupalSettings.tc[field].datasets[0].strokeColor = "rgba(110,110,220,1)";
      var f = {
        fillColor: "rgba(220,220,220,0.2)",
        strokeColor: "rgba(220,220,220,1)",
        pointColor: "rgba(220,220,220,1)",
        pointStrokeColor: "#fff",
        pointHighlightFill: "#fff",
        pointHighlightStroke: "rgba(220,220,220,1)"
      };
      var myNewChart = new Chart(ctx).Line(drupalSettings.tc[field], {
        pointDot: false,
        datasetFill: false
      });
    });
  }
}

})(jQuery);
