/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

(function ($, window, Drupal) {
  'use strict';
//  var height = 300;
//	var width = 600;
  Drupal.behaviors.node_like_dislike_field = {
    attach: function (context, settings) {
      if (settings.node_like_dislike_field) {
        var j = settings.node_like_dislike_field.first;
        var i = settings.node_like_dislike_field.second;
        var barData = [{
          x: 'Likes',
          y: j
        }, {
          x: 'Dislikes',
          y: i
        }];
        var height = 400;
        var width = 300;
        d3.select('#graph').select('svg').remove();
        var vis = d3.select('#graph').append('svg:svg').attr('height', height)
                .attr('width', width).attr('class', 'sv');
        var WIDTH = 300;
        var HEIGHT = 400;
        var MARGINS = {
          top: 20,
          right: 20,
          bottom: 20,
          left: 50
        };
        var xRange = d3.scale.ordinal().rangeRoundBands([MARGINS.left, WIDTH - MARGINS.right], 0.1).domain(barData.map(function (d) {
          return d.x;
        }));
        var yRange = d3.scale.linear().range([HEIGHT - MARGINS.top, MARGINS.bottom]).domain([0, 40]);
        var xAxis = d3.svg.axis().scale(xRange).tickSize(5).tickSubdivide(true);
        var yAxis = d3.svg.axis().scale(yRange).tickSize(5).orient('left')
                .tickSubdivide(true);

        vis.append('svg:g')
                .attr('class', 'x axis')
                .attr('transform', 'translate(0,' + (HEIGHT - MARGINS.bottom) + ')')
                .call(xAxis);

        vis.append('svg:g')
                .attr('class', 'y axis')
                .attr('transform', 'translate(' + (MARGINS.left) + ',0)')
                .call(yAxis);

        vis.selectAll('rect')
                .data(barData)
                .enter()
                .append('rect')
                .attr('x', function (d) {
                  return xRange(d.x);
                })
                .attr('y', function (d) {
                  return yRange(d.y);
                })
                .attr('width', xRange.rangeBand())
                .attr('height', function (d) {
                  return ((HEIGHT - MARGINS.bottom) - yRange(d.y));
                })
                .attr('fill', 'grey')
                .on('mouseover', function (d) {
                  d3.select(this)
                          .attr('fill', 'blue');
                })
                .on('mouseout', function (d) {
                  d3.select(this)
                          .attr('fill', 'grey');
                });

        var lineData = (($('#graphResult> div').text())) ? JSON.parse($('#graphResult> div').text()) : '';
        var lineData1 = (($('#graphResult1> div').text())) ? JSON.parse($('#graphResult1> div').text()) : '';
        var Data = [
          {
            name: 'LIKES',
            WeeklyData: lineData
          },
          {
            name: 'DISLIKES',
            WeeklyData: lineData1
          }
        ];
        var margin = {top: 20, right: 80, bottom: 30, left: 50};
        width = 800 - margin.left - margin.right;
        height = 300 - margin.top - margin.bottom;

        var parseDate = d3.time.format('%d-%b');

        var x = d3.scale.ordinal()
                .rangeRoundBands([0, width]);

        var y = d3.scale.linear()
                .range([height, 0]);

        var color = d3.scale.category10();

        xAxis = d3.svg.axis()
                .scale(x)
                .orient('bottom');

        yAxis = d3.svg.axis()
                .scale(y)
                .orient('left')
                .ticks(10);
        var weekly = Data[0].WeeklyData ? Data[0].WeeklyData : null;
        // xData gives an array of distinct 'Weeks' for which trends chart is going to be made.
        if (weekly != null) {
          var xData = weekly.map(function (d) {
            return parseDate(new Date(d.date_timestamp));
          });


          var line = d3.svg.line()
                  .x(function (d) {
                    return x(parseDate(new Date(d.date_timestamp))) + x.rangeBand() / 2;
                  })
                  .y(function (d) {
                    return y(d.likes);
                  });
          d3.select('#graph1').select('svg').remove();
          var svg = d3.select('#graph1').append('svg')
                  .attr('width', width + margin.left + margin.right)
                  .attr('height', height + margin.top + margin.bottom)
                  .append('g')
                  .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');

          color.domain(Data.map(function (d) {
            return d.name;
          }));

          x.domain(xData);

          var valueMax = d3.max(Data, function (r) {
            return d3.max(r.WeeklyData, function (d) {
              return d.likes;
            });
          });
          var valueMin = d3.min(Data, function (r) {
            return d3.min(r.WeeklyData, function (d) {
              return d.likes;
            });
          });
          y.domain([valueMin, valueMax]);
          svg.append('g')
                  .attr('class', 'x axis')
                  .attr('transform', 'translate(0,' + height + ')')
                  .call(xAxis);

          // Drawing Horizontal grid lines.
          svg.append('g')
                  .attr('class', 'GridX')
                  .selectAll('line.grid').data(y.ticks()).enter()
                  .append('line')
                  .attr({
                    class: 'grid',
                    x1: x(xData[0]),
                    x2: x(xData[xData.length - 1]) + x.rangeBand() / 2,
                    y1: function (d) {
                      return y(d);
                    },
                    y2: function (d) {
                      return y(d);
                    }});
          // Drawing Y Axis
          svg.append('g')
                  .attr('class', 'y axis')
                  .call(yAxis)
                  .append('text')
                  .attr('transform', 'rotate(-90)')
                  .attr('y', 6)
                  .attr('dy', '.71em')
                  .style('text-anchor', 'end')
                  .text('No. of users(likes/dislikes)');

          // Drawing Lines for each segments
          var segment = svg.selectAll('.segment')
                  .data(Data)
                  .enter().append('g')
                  .attr('class', 'segment');

          segment.append('path')
                  .attr('class', 'line')
                  .attr('id', function (d) {
                    return d.name;
                  })
                  .attr('visible', 1)
                  .attr('d', function (d) {
                    return line(d.WeeklyData);
                  })
                  .style('stroke', function (d) {
                    return color(d.name);
                  });
          // Creating Dots on line
          segment.selectAll('dot')
                  .data(function (d) {
                    return d.WeeklyData;
                  })
                  .enter().append('circle')
                  .attr('r', 5)
                  .attr('cx', function (d) {
                    return x(parseDate(new Date(d.date_timestamp))) + x.rangeBand() / 2;
                  })
                  .attr('cy', function (d) {
                    return y(d.likes);
                  })
                  .style('stroke', 'white')
                  .style('fill', function (d) {
                    return color(this.parentNode.__data__.name);
                  })
                  .on('mouseover', mouseover)
                  .on('mousemove', function (d) {
                    divToolTip
                            .text(this.parentNode.__data__.name + ' : ' + d.likes)
                            .style('left', (d3.event.pageX + 15) + 'px')
                            .style('top', (d3.event.pageY - 10) + 'px');
                  })
                  .on('mouseout', mouseout);
          segment.append('text')
                  .datum(function (d) {
                    return {name: d.name, RevData: d.WeeklyData[d.WeeklyData.length - 1]};
                  })
                  .attr('transform', function (d) {
                    if (d.RevData) {
                      var xpos = x(parseDate(new Date(d.RevData.date_timestamp))) + x.rangeBand() / 2;
                      return 'translate(' + xpos + ',' + y(d.RevData.likes) + ')';
                    }
                  })
                  .attr('x', 3)
                  .attr('dy', '.35em')
                  .attr('class', 'segmentText')
                  .attr('Segid', function (d) {
                    return d.name;
                  });
          // .text(function (d) { return d.name; });

          d3.selectAll('.segmentText').on('click', function (d) {
            var tempId = d3.select(this).attr('Segid');
            var flgVisible = d3.select('#' + tempId).attr('visible');

            var newOpacity = flgVisible === 1 ? 0 : 1;
            flgVisible = flgVisible === 1 ? 0 : 1;

            // Hide or show the elements
            d3.select('#' + tempId).style('opacity', newOpacity)
                    .attr('visible', flgVisible);

          });
        }
        // Adding Tooltip
      }
      var divToolTip = d3.select('body').append('div')
              .attr('class', 'tooltip')
              .style('opacity', 1e-6);
      function mouseover() {
        divToolTip.transition()
          .duration(500)
          .style('opacity', 1);
      }
      function mouseout() {
        divToolTip.transition()
          .duration(500)
          .style('opacity', 1e-6);
      }
    }
  };
})(jQuery, window, Drupal);
