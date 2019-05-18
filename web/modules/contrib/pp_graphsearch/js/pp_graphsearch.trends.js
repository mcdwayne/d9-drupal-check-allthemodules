
/**
 * Plots the trend chart for the selected concepts
 */
var plotData = function(id, content) {
  jQuery.plot(jQuery("#"+id), content, {
    xaxis: {
      mode:"time",
      tickSize:[1,"month"],
      tickLength: 0,
      show:true
    },
    yaxis: {
      tickDecimals:0,
      show:false
    },
    legend: {
      labelBoxBorderColor:"none",
      container:jQuery("#flot-chart-legend"),
      show:true
    },
    grid: {
      hoverable:true,
      borderWidth:1,
      autoHighlight:true
    },
    shadowSize: 0
  });
};

/**
 * Initializes the tooltip for the trend chart
 */
var useTooltipp = function(id) {
  var previousPoint = null;
  var previousLabel = null;
  var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

  jQuery("#"+id).bind("plothover", function(event, pos, item) {
    if (item) {
      if ((previousLabel != item.series.label) || (previousPoint != item.dataIndex)) {
        previousLabel = item.series.label;
        previousPoint = item.dataIndex;
        jQuery("#trends-tooltip").remove();

        var x = item.datapoint[0];
        var y = item.datapoint[1];

        //console.log(item);
        var value = Math.round(y * 100) / 100;
        var time  = new Date(x);
        var date_string = time.getFullYear() + "-" + monthNames[time.getMonth()] + "-" + time.getDate();
        var color = item.series.color;

        showTooltip(item.pageX, item.pageY, color,
          "<strong>" + item.series.label + " (" + value + ")</strong><br>" + date_string);
      }
    } else {
      jQuery("#trends-tooltip").remove();
    }
  });
};

/**
 * Shows the tooltip
 */
var showTooltip= function(x, y, color, contents) {
  jQuery('<div id="trends-tooltip">' + contents + '</div>').css({
    position: 'absolute',
    display: 'none',
    top: y - 30,
    left: x + 10,
    border: '2px solid ' + color,
    padding: '3px',
    'font-size': '9px',
    'border-radius': '5px',
    'background-color': '#fff',
    'font-family': 'Verdana, Arial, Helvetica, Tahoma, sans-serif',
    opacity: 0.9
  }).appendTo("body").fadeIn(200);
};
