/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.flot_examples = {
    attach: function () {
      // Randomly Generated Data.
      var data = [];
      var series = Math.floor(Math.random() * 6) + 3;
      for (var i = 0; i < series; i++) {
        data[i] = {
          label: 'Series' + (i + 1),
          data: Math.floor(Math.random() * 100) + 1
        };
      }
      var placeholder = $('#placeholder');
      $('#example-1').click(function () {
        placeholder.unbind();
        $('#title').text(Drupal.t('Default pie chart'));
        $('#description').text(Drupal.t('The default pie chart with no options set.'));
        var options = {series: {pie: {show: true}}};
        $.plot(placeholder, data, options);
        setCode(options);
      });
      $('#example-2').click(function () {
        placeholder.unbind();
        $('#title').text(Drupal.t('Default without legend'));
        $('#description').text(Drupal.t('The default pie chart when the legend is disabled. Since the labels would normally be outside the container, the chart is resized to fit.'));
        var options = {
          series: {
            pie: {
              show: true
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);
        setCode(options);
      });
      $('#example-3').click(function () {
        placeholder.unbind();
        $('#title').text(Drupal.t('Custom Label Formatter'));
        $('#description').text(Drupal.t('Added a semi-transparent background to the labels and a custom labelFormatter function.'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 1,
              label: {
                show: true,
                radius: 1,
                formatter: labelFormatter,
                background: {
                  opacity: 0.8
                }
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-4').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Label Radius'));
        $('#description').text(Drupal.t('Slightly more transparent label backgrounds and adjusted the radius values to place them within the pie.'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 1,
              label: {
                show: true,
                radius: 3 / 4,
                formatter: labelFormatter,
                background: {
                  opacity: 0.5
                }
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-5').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Label Styles #1'));
        $('#description').text(Drupal.t('Semi-transparent, black-colored label background.'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 1,
              label: {
                show: true,
                radius: 3 / 4,
                formatter: labelFormatter,
                background: {
                  opacity: 0.5,
                  color: '#000'
                }
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-6').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Label Styles #2'));
        $('#description').text(Drupal.t('Semi-transparent, black-colored label background placed at pie edge.'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 3 / 4,
              label: {
                show: true,
                radius: 3 / 4,
                formatter: labelFormatter,
                background: {
                  opacity: 0.5,
                  color: '#000'
                }
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-7').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Hidden Labels'));
        $('#description').text(Drupal.t('Labels can be hidden if the slice is less than a given percentage of the pie (10% in this case).'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 1,
              label: {
                show: true,
                radius: 2 / 3,
                formatter: labelFormatter,
                threshold: 0.1
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-8').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Combined Slice'));
        $('#description').text(Drupal.t('Multiple slices less than a given percentage (5% in this case) of the pie can be combined into a single, larger slice.'));
        var options = {
          series: {
            pie: {
              show: true,
              combine: {
                color: '#999',
                threshold: 0.05
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-9').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Rectangular Pie'));
        $('#description').text(Drupal.t('The radius can also be set to a specific size (even larger than the container itself).'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 500,
              label: {
                show: true,
                formatter: labelFormatter,
                threshold: 0.1
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-10').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Tilted Pie'));
        $('#description').text(Drupal.t('The pie can be tilted at an angle.'));
        var options = {
          series: {
            pie: {
              show: true,
              radius: 1,
              tilt: 0.5,
              label: {
                show: true,
                radius: 1,
                formatter: labelFormatter,
                background: {
                  opacity: 0.8
                }
              },
              combine: {
                color: '#999',
                threshold: 0.1
              }
            }
          },
          legend: {
            show: false
          }
        };
        $.plot(placeholder, data, options);
        setCode(options);
      });

      $('#example-11').click(function () {

        placeholder.unbind();

        $('#title').text(Drupal.t('Donut Hole'));
        $('#description').text(Drupal.t('A donut hole can be added.'));
        var options = {
          series: {
            pie: {
              innerRadius: 0.5,
              show: true
            }
          }
        };
        $.plot(placeholder, data, options);

        setCode(options);
      });

      $('#example-12').click(function () {
        placeholder.unbind();
        $('#title').text(Drupal.t('Interactivity'));
        $('#description').text(Drupal.t('The pie can be made interactive with hover and click events.'));
        var options = {
          series: {
            pie: {
              show: true
            }
          },
          grid: {
            hoverable: true,
            clickable: true
          }
        };
        $.plot(placeholder, data, options);
        setCode(options);
        placeholder.bind('plothover', function (event, pos, obj) {
          if (!obj) {
            return;
          }
          var percent = parseFloat(obj.series.percent).toFixed(2);
          $('#hover').html("<span style='font-weight:bold; color:" + obj.series.color + "'>" + obj.series.label + ' (' + percent + '%)</span>');
        });
        placeholder.bind('plotclick', function (event, pos, obj) {
          if (!obj) {
            return;
          }
          percent = parseFloat(obj.series.percent).toFixed(2);
          alert('' + obj.series.label + ': ' + percent + '%');
        });
      });
      // Show the initial default chart.
      $('#example-1').click();
      // Add the Flot version string to the footer.
      $('#footer').prepend('Flot ' + $.plot.version + ' &ndash; ');
      // A custom label formatter used by several of the plots.
      function labelFormatter(label, series) {
        return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + '<br/>' + Math.round(series.percent) + '%</div>';
      }
      function setCode(options) {
        $('#code').text("$.plot('#placeholder', data, " + JSON.stringify(options, null, 2) + ');');
      }
    }
  };
}(jQuery, Drupal, drupalSettings));
