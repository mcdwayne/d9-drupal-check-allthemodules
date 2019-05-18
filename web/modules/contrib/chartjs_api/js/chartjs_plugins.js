/**
 * @file
 * Contains js for chartjs plugins.
 */

/* eslint-disable no-unused-vars */

var halfdonutTotal = {
  beforeDraw: function (chart) {

    'use strict';

    // Define vars.
    var width = chart.width;
    var height = chart.chart.height;
    var ctx = chart.chart.ctx;

    // Calculate position of text.
    if (chart.legend.options.display === true) {
      if (chart.legend.position === 'bottom') {
        height = height - chart.legend.height;
      }
    }
    ctx.restore();

    // Calculate font size.
    var fontSize = (height / 60).toFixed(2);
    ctx.font = fontSize + 'em sans-serif';

    // Set aligns.
    ctx.textBaseline = 'bottom';
    ctx.textAlign = 'center';

    // Crate text.
    var text = chart.options.title.text;
    var textX = Math.round(width / 2);
    ctx.fillText(text, textX, height);
    ctx.save();
  }
};

/* eslint-enable no-unused-vars */
