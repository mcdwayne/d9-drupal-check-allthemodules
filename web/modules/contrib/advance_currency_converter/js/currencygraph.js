/**
 * @file
 * It will help us to create a graph using json data.
 */

(function ($, window, drupalSettings) {
  'use strict';
  Drupal.behaviors.currency = {
    attach: function (context, settings) {
      $('#frontpanel .form-submit').once().on('click', function () {
        setTimeout(function () {
          InitChart();
          function InitChart() {
            var lineData = JSON.parse(($('#graphResult div').text()));
            var vis = d3.select('#genrateGraph');
            var WIDTH = 200;
            var HEIGHT = 200;
            var MARGINS = {
              top: 10,
              right: 10,
              bottom: 10,
              left: 70
            };
            var xRange = d3.scale.linear().range([MARGINS.left, WIDTH - MARGINS.right]).domain(d3xRange());
            function d3xRange() {
              return [d3.min(lineData, dDateX), d3.max(lineData, dDateX)];
            }
            function dDateX(d) {
              return d.date;
            }
            var yRange = d3.scale.linear().range([HEIGHT - MARGINS.top, MARGINS.bottom]).domain(d3yRange());
            function d3yRange() {
              return [d3.min(lineData, dPriceY), d3.max(lineData, dPriceY)];
            }
            function dPriceY(d) {
              return d.price;
            }
            var xAxis = d3.svg.axis()
              .scale(xRange)
              .tickSize(4)
              .tickSubdivide(true)
              .tickFormat(d3.format('d'));
            var yAxis = d3.svg.axis()
              .scale(yRange)
              .tickSize(8)
              .orient('left')
              .tickSubdivide(true);

            vis.append('svg:g')
              .attr('class', 'x axis')
              .attr('transform', 'translate(0,' + (HEIGHT - MARGINS.bottom) + ')')
              .call(xAxis);

            vis.append('svg:g')
              .attr('class', 'y axis')
              .attr('transform', 'translate(' + (MARGINS.left) + ',0)')
              .call(yAxis);
            var lineFunc = d3.svg.line()
              .x(dxRange)
              .y(dyRange)
              .interpolate('linear');
            vis.append('svg:path')
              .attr('d', lineFunc(lineData))
              .attr('stroke', 'blue')
              .attr('stroke-width', 2)
              .attr('fill', 'none');
            function dxRange(d) {
              return xRange(d.date);
            }
            function dyRange(d) {
              return yRange(d.price);
            }
          }
        }, 1500);
      });
    }
  };
})(jQuery, window, drupalSettings);
