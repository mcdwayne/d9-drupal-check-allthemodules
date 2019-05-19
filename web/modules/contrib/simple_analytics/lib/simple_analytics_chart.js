/**
 * Simple analytics chart.
 */
function showchart(chart_id, data, chart_type) {
    var options = {
        onlyInteger: false,
        scaleMinSpace: 20,
        fullWidth: true,
        chartPadding: {right: 40},
        axisX: {
            labelInterpolationFnc: function (value, index) {
                return index % 7 === 0 ? value : null;
            }
        }
    };
    var responsiveOptions = [
        ['screen and (max-width: 640px)', {
                axisX: {
                    labelInterpolationFnc: function (value, index) {
                        return index % 7 === 0 ? value : null;
                    }
                }
            }]
    ];
    if (chart_type === 'Line') {
        new Chartist.Line(chart_id, data, options, responsiveOptions);
    }
}
