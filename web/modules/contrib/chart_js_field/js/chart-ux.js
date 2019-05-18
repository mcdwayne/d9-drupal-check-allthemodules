// Javascript to add ux for filling out the data and options of chart.js

(function($, Drupal, Chart) {
  function rgbaToHex(rgba) {
    const regrgba = /rgba\(([\d]{1,3}),[\s]([\d]{1,3}),[\s]([\d]{1,3}),[\s]([0|1]\.?[\d]*)\)/g;
    const vals = regrgba.exec(rgba);

    if (vals) {
      let r = parseInt(vals[1], 10).toString(16);
      if (r.length < 2) {
        r = `0${r}`;
      }
      let g = parseInt(vals[2], 10).toString(16);
      if (g.length < 2) {
        g = `0${g}`;
      }
      let b = parseInt(vals[3], 10).toString(16);
      if (b.length < 2) {
        b = `0${b}`;
      }
      return [`#${r}${g}${b}`, parseFloat(vals[4])];
    }
    return ["#000000", 1];
  }

  function hexToRgba(hex, opacity) {
    const r = parseInt(hex.substr(1, 2), 16);
    const g = parseInt(hex.substr(3, 2), 16);
    const b = parseInt(hex.substr(5, 2), 16);

    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
  }

  Drupal.behaviors.chart_ux = {
    attach(context) {
      $(".chart-js-field", context)
        .once("chart_ux")
        .each((i, field) => {
          const chartSettings = drupalSettings.chart_ux;
          const dataField = $(field).find('textarea[name$="[data]"]');
          const optionsField = $(field).find('textarea[name$="[options]"]');
          let data;
          let optionsVal;
          let opendata = false;
          let openoptions = false;
          let openchart = false;
          const chartTypeField = $(field).find(".chart-type-select");
          let chartType = $(chartTypeField).val();
          const uid = Math.random()
            .toString(36)
            .substr(2, 9);

          const defaultData = {
            showlabels: chartSettings.data.allow_labels,
            labels: [],
            datasets: [
              {
                label: "",
                datatype: chartSettings.data.type,
                data: [],
                backgroundColor: [],
                borderColor: [],
                borderWidth: chartSettings.data.brdw
              }
            ]
          };

          const defaultOptions = {
            title: {
              display: chartSettings.options.display_title,
              text: "Chart Title"
            },
            scales: {
              xAxes: [
                {
                  ticks: {
                    beginAtZero: chartSettings.options.default_zero_x
                  },
                  scaleLabel: {
                    display: chartSettings.options.x_axis_display,
                    labelString: chartSettings.options.x_axis_label
                  },
                  gridLines: {
                    display: chartSettings.options.x_grid
                  }
                }
              ],
              yAxes: [
                {
                  ticks: {
                    beginAtZero: chartSettings.options.default_zero_y
                  },
                  scaleLabel: {
                    display: chartSettings.options.y_axis_display,
                    labelString: chartSettings.options.y_axis_label
                  },
                  gridLines: {
                    display: chartSettings.options.y_grid
                  }
                }
              ]
            },
            maintainAspectRatio: chartSettings.options.maintain_aspect_ratio,
            responsive: chartSettings.options.responsive
          };

          if (dataField.val()) {
            data = JSON.parse(dataField.val());
          } else {
            data = defaultData;
          }

          if (optionsField.val()) {
            optionsVal = JSON.parse(optionsField.val());
          } else {
            optionsVal = defaultOptions;
          }

          const previewChart = `
            <details class="chart-preview">
              <summary>Chart Preview</summary>
              <canvas id="myChart-${uid}" width="400" height="400"></canvas>
            </details>`;

          $(field).append(previewChart);

          let ctx = document.getElementById(`myChart-${uid}`).getContext("2d");
          let myChart = new Chart(ctx, {
            type: chartType !== "_none" ? chartType : "line",
            data,
            options: optionsVal
          });

          function updatePreview() {
            myChart.data = data;
            myChart.options = optionsVal;
            myChart.type = chartType !== "_none" ? chartType : "line";
            myChart.update();
          }

          function setDataFunc(dataset, setElem) {
            const setData = [];
            if (dataset.datatype === "value") {
              $(setElem)
                .find(".data-y")
                .each((index, datax) => {
                  setData.push($(datax).val());
                });
            } else {
              $(setElem)
                .find(".data-x")
                .each((index, datax) => {
                  const point = {};
                  point.x = $(datax).val();
                  point.y = $(setElem)
                    .find(".data-y")
                    .get(index).value;
                  setData.push(point);
                });
            }

            return setData;
          }

          function setBackgroundColor(setElem) {
            const backgroundColor = [];
            let rgba;
            if (chartSettings.data.allow_bgc) {
              $(setElem)
                .find(".data-background")
                .each((index, background) => {
                  if (chartSettings.data.allow_bgo) {
                    rgba = hexToRgba(
                      $(background).val(),
                      $(setElem)
                        .find(".data-background-opacity")
                        .get(index).value
                    );
                  } else {
                    rgba = hexToRgba(
                      $(background).val(),
                      chartSettings.data.bgo
                    );
                  }
                  backgroundColor.push(rgba);
                });
            } else {
              $(setElem)
                .find(".data-y")
                .each(index => {
                  if (chartSettings.data.allow_bgo) {
                    rgba = hexToRgba(
                      chartSettings.data.bgc,
                      $(setElem)
                        .find(".data-background-opacity")
                        .get(index).value
                    );
                  } else {
                    rgba = hexToRgba(
                      chartSettings.data.bgc,
                      chartSettings.data.bgo
                    );
                  }
                  backgroundColor.push(rgba);
                });
            }

            return backgroundColor;
          }

          function setBorderColor(setElem) {
            const borderColor = [];
            let rgba;
            if (chartSettings.data.allow_brdc) {
              $(setElem)
                .find(".data-border")
                .each((index, border) => {
                  if (chartSettings.data.allow_brdo) {
                    rgba = hexToRgba(
                      $(border).val(),
                      $(setElem)
                        .find(".data-border-opacity")
                        .get(index).value
                    );
                  } else {
                    rgba = hexToRgba($(border).val(), chartSettings.data.brdo);
                  }
                  borderColor.push(rgba);
                });
            } else {
              $(setElem)
                .find(".data-y")
                .each(index => {
                  if (chartSettings.data.allow_brdo) {
                    rgba = hexToRgba(
                      chartSettings.data.brdc,
                      $(setElem)
                        .find(".data-border-opacity")
                        .get(index).value
                    );
                  } else {
                    rgba = hexToRgba(
                      chartSettings.data.brdc,
                      chartSettings.data.brdo
                    );
                  }
                  borderColor.push(rgba);
                });
            }

            return borderColor;
          }

          function updateData() {
            const newData = {};
            const newOptions = {
              title: {
                display: chartSettings.options.display_title,
                text: "Chart Title"
              },
              scales: {
                xAxes: [
                  {
                    ticks: {
                      beginAtZero: chartSettings.options.default_zero_x
                    },
                    scaleLabel: {
                      display: chartSettings.options.x_axis_display,
                      labelString: chartSettings.options.x_axis_label
                    },
                    gridLines: {
                      display: chartSettings.options.x_grid
                    }
                  }
                ],
                yAxes: [
                  {
                    ticks: {
                      beginAtZero: chartSettings.options.default_zero_y
                    },
                    scaleLabel: {
                      display: chartSettings.options.y_axis_display,
                      labelString: chartSettings.options.y_axis_label
                    },
                    gridLines: {
                      display: chartSettings.options.y_grid
                    }
                  }
                ]
              }
            };

            if (chartSettings.data.allow_labels) {
              newData.showlabels = data.showlabels;

              newData.labels = [];
              $(field)
                .find(".data-labels")
                .each(function() {
                  newData.labels.push($(this).val());
                });
            }

            newData.datasets = [];
            $(field)
              .find(".data-dataset")
              .each(function() {
                const setElem = this;
                const dataset = {};

                dataset.label = $(setElem)
                  .find(".data-label")
                  .val();

                if (chartSettings.data.allow_type) {
                  dataset.datatype = $(setElem)
                    .find(".data-type")
                    .val();
                } else {
                  dataset.datatype = chartSettings.data.type;
                }

                dataset.data = setDataFunc(dataset, setElem);

                dataset.backgroundColor = setBackgroundColor(setElem);

                dataset.borderColor = setBorderColor(setElem);

                if (chartSettings.data.allow_brdw) {
                  dataset.borderWidth = $(setElem)
                    .find(".data-border-width")
                    .val();
                } else {
                  dataset.borderWidth = chartSettings.data.brdw;
                }

                newData.datasets.push(dataset);
              });

            newOptions.title.text = $(field)
              .find(".data-chart-title")
              .val();

            if (chartSettings.options.allow_display_title) {
              if (
                $(field)
                  .find(".data-display-title")
                  .prop("checked")
              ) {
                newOptions.title.display = true;
              } else {
                newOptions.title.display = false;
              }
            }

            if (chartSettings.options.allow_zero_x) {
              if (
                $(field)
                  .find(".data-x-zero")
                  .prop("checked")
              ) {
                newOptions.scales.xAxes[0].ticks.beginAtZero = true;
              } else {
                newOptions.scales.xAxes[0].ticks.beginAtZero = false;
              }
            } else {
              newOptions.scales.xAxes[0].ticks.beginAtZero =
                chartSettings.options.default_zero_x;
            }

            if (chartSettings.options.allow_x_min) {
              if (
                $(field)
                  .find(".data-x-min")
                  .val()
              ) {
                newOptions.scales.xAxes[0].ticks.min = parseInt(
                  $(field)
                    .find(".data-x-min")
                    .val(),
                  10
                );
              }
            } else if (chartSettings.options.x_min)
              newOptions.scales.xAxes[0].ticks.min =
                chartSettings.options.x_min;
            if (chartSettings.options.allow_x_max) {
              if (
                $(field)
                  .find(".data-x-max")
                  .val()
              ) {
                newOptions.scales.xAxes[0].ticks.max = parseInt(
                  $(field)
                    .find(".data-x-max")
                    .val(),
                  10
                );
              }
            } else if (chartSettings.options.x_max)
              newOptions.scales.xAxes[0].ticks.max =
                chartSettings.options.x_max;
            if (chartSettings.options.allow_x_step) {
              if (
                $(field)
                  .find(".data-x-step")
                  .val()
              ) {
                newOptions.scales.xAxes[0].ticks.stepSize = parseInt(
                  $(field)
                    .find(".data-x-step")
                    .val(),
                  10
                );
              }
            } else if (chartSettings.options.x_step)
              newOptions.scales.xAxes[0].ticks.stepSize =
                chartSettings.options.x_step;

            if (chartSettings.options.allow_x_axis_display) {
              if (
                $(field)
                  .find(".data-x-axis-display")
                  .prop("checked")
              ) {
                newOptions.scales.xAxes[0].scaleLabel.display = true;
              } else {
                newOptions.scales.xAxes[0].scaleLabel.display = false;
              }
            } else {
              newOptions.scales.xAxes[0].scaleLabel.display =
                chartSettings.options.x_axis_display;
            }
            if (chartSettings.options.allow_x_axis_label) {
              newOptions.scales.xAxes[0].scaleLabel.labelString = $(field)
                .find(".data-x-axis-label")
                .val();
            } else {
              newOptions.scales.xAxes[0].scaleLabel.labelString =
                chartSettings.options.x_axis_label;
            }
            if (chartSettings.options.allow_x_grid) {
              if (
                $(field)
                  .find(".data-x-grid")
                  .prop("checked")
              ) {
                newOptions.scales.xAxes[0].gridLines.display = true;
              } else {
                newOptions.scales.xAxes[0].gridLines.display = false;
              }
            } else {
              newOptions.scales.xAxes[0].gridLines.display =
                chartSettings.options.x_grid;
            }

            if (chartSettings.options.allow_zero_y) {
              if (
                $(field)
                  .find(".data-y-zero")
                  .prop("checked")
              ) {
                newOptions.scales.yAxes[0].ticks.beginAtZero = true;
              } else {
                newOptions.scales.yAxes[0].ticks.beginAtZero = false;
              }
            } else {
              newOptions.scales.yAxes[0].ticks.beginAtZero =
                chartSettings.options.default_zero_y;
            }

            if (chartSettings.options.allow_y_min) {
              if (
                $(field)
                  .find(".data-y-min")
                  .val()
              ) {
                newOptions.scales.yAxes[0].ticks.min = parseInt(
                  $(field)
                    .find(".data-y-min")
                    .val(),
                  10
                );
              }
            } else if (chartSettings.options.y_min)
              newOptions.scales.yAxes[0].ticks.min =
                chartSettings.options.y_min;
            if (chartSettings.options.allow_y_max) {
              if (
                $(field)
                  .find(".data-y-max")
                  .val()
              ) {
                newOptions.scales.yAxes[0].ticks.max = parseInt(
                  $(field)
                    .find(".data-y-max")
                    .val(),
                  10
                );
              }
            } else if (chartSettings.options.y_max)
              newOptions.scales.yAxes[0].ticks.max =
                chartSettings.options.y_max;
            if (chartSettings.options.allow_y_step) {
              if (
                $(field)
                  .find(".data-y-step")
                  .val()
              ) {
                newOptions.scales.yAxes[0].ticks.stepSize = parseInt(
                  $(field)
                    .find(".data-y-step")
                    .val(),
                  10
                );
              }
            } else if (chartSettings.options.y_step)
              newOptions.scales.yAxes[0].ticks.stepSize =
                chartSettings.options.y_step;

            if (chartSettings.options.allow_y_axis_display) {
              if (
                $(field)
                  .find(".data-y-axis-display")
                  .prop("checked")
              ) {
                newOptions.scales.yAxes[0].scaleLabel.display = true;
              } else {
                newOptions.scales.yAxes[0].scaleLabel.display = false;
              }
            } else {
              newOptions.scales.yAxes[0].scaleLabel.display =
                chartSettings.options.y_axis_display;
            }

            if (chartSettings.options.allow_y_axis_label) {
              newOptions.scales.yAxes[0].scaleLabel.labelString = $(field)
                .find(".data-y-axis-label")
                .val();
            } else {
              newOptions.scales.yAxes[0].scaleLabel.labelString =
                chartSettings.options.y_axis_label;
            }

            if (chartSettings.options.allow_y_grid) {
              if (
                $(field)
                  .find(".data-y-grid")
                  .prop("checked")
              ) {
                newOptions.scales.yAxes[0].gridLines.display = true;
              } else {
                newOptions.scales.yAxes[0].gridLines.display = false;
              }
            } else {
              newOptions.scales.yAxes[0].gridLines.display =
                chartSettings.options.y_grid;
            }

            if (chartSettings.options.allow_maintain_aspect_ratio) {
              if (
                $(field)
                  .find(".maintain-aspect-ratio")
                  .prop("checked")
              ) {
                newOptions.maintainAspectRatio = true;
              } else {
                newOptions.maintainAspectRatio = false;
              }
            } else {
              newOptions.maintainAspectRatio =
                chartSettings.options.maintainAspectRatio;
            }

            if (chartSettings.options.allow_responsive) {
              if (
                $(field)
                  .find(".responsive-option")
                  .prop("checked")
              ) {
                newOptions.responsive = true;
              } else {
                newOptions.responsive = false;
              }
            } else {
              newOptions.responsive = chartSettings.options.responsive;
            }

            data = newData;
            optionsVal = newOptions;

            $(dataField).val(JSON.stringify(data));
            $(optionsField).val(JSON.stringify(optionsVal));
            updatePreview();
          }

          function removeData(set, index) {
            $(field)
              .find(`.data-point[data-set="${set}"][data-index="${index}"]`)
              .remove();
            updateData();
          }

          function removeDataset(set) {
            $(field)
              .find(`.data-dataset[data-set="${set}"]`)
              .remove();
            updateData();
          }

          function addData(set, index, datatype) {
            let dataset = `<div class="data-point" data-set="${set}" data-index="${index}">`;

            if (datatype === "value") {
              dataset +=
                `<div><input style="display: none;" type="number" step="0.001" class="data-x" data-index="${index}" data-set="${set}"></div>` +
                `<div><input type="number" step="0.001" class="data-y" data-index="${index}" data-set="${set}"></div>`;
            } else {
              dataset +=
                `<div><input type="number" step="0.001" class="data-x" data-index="${index}" data-set="${set}"></div>` +
                `<div><input type="number" step="0.001" class="data-y" data-index="${index}" data-set="${set}"></div>`;
            }

            if (chartSettings.data.allow_bgc) {
              dataset += `<div><input type="color" class="data-background" data-index="${index}" data-set="${set}" value="${
                chartSettings.data.bgc
              }"></div>`;
            }

            if (chartSettings.data.allow_bgo) {
              dataset += `<div><input type="number" min="0" max="1.0" step="0.05" class="data-background-opacity" data-index="${index}" data-set="${set}" value="${
                chartSettings.data.bgo
              }"></div>`;
            }

            if (chartSettings.data.allow_brdc) {
              dataset += `<div><input type="color" class="data-border" data-index="${index}" data-set="${set}" value="${
                chartSettings.data.brdc
              }"></div>`;
            }

            if (chartSettings.data.allow_brdo) {
              dataset += `<div><input type="number" min="0" max="1.0" step="0.05" class="data-border-opacity" data-index="${index}" data-set="${set}" value="${
                chartSettings.data.brdo
              }"></div>`;
            }

            dataset +=
              `<button class="button remove-data" data-set="${set}" data-index="${index}">Remove data</button>` +
              "</div>";

            $(field)
              .find(`.data-dataset[data-set="${set}"]`)
              .find(".data-set-wrapper")
              .append(dataset);
            updateData();
            setTimeout(() => {
              updateForm();
            }, 50);
          }

          function addDataset() {
            const setIndex = data.datasets.length;
            let dataset =
              `<div class="data-dataset" data-set="${setIndex}">` +
              `<input type="text" class="data-label" data-set="${setIndex}">`;

            if (chartSettings.data.allow_brdw) {
              dataset += `<input type="number" class="data-border-width" data-set="${setIndex}" value="1">`;
            }

            if (chartSettings.data.allow_type) {
              dataset += '<select class="data-type">';

              if (chartSettings.data.type === "value") {
                dataset +=
                  '<option value="value" selected>Value</option>' +
                  '<option value="point">Point</option>';
              } else {
                dataset +=
                  '<option value="value">Value</option>' +
                  '<option value="point" selected>Point</option>';
              }

              dataset += "</select>";
            }

            dataset +=
              `${'<div class="data-set-wrapper">' +
                "</div>" +
                '<button class="add-data button" data-set="'}${setIndex}" data-index="0">Add data</button>` +
              `<button class="remove-dataset button" data-set="${setIndex}">Remove dataset</button>` +
              `</div>`;

            $(field)
              .find(".add-dataset")
              .before(dataset);
          }

          function drawRow(dataset, setIndex) {
            let dataUX = "";
            dataset.data.forEach((item, index) => {
              const backgroundColor = rgbaToHex(dataset.backgroundColor[index]);
              const borderColor = rgbaToHex(dataset.borderColor[index]);

              dataUX += `<div class="data-point" data-set="${setIndex}" data-index="${index}">`;

              if (dataset.datatype === "value") {
                dataUX +=
                  `<div><input style="display: none;" type="number" step="0.001" class="data-x" data-index="${index}" data-set="${setIndex}"></div>` +
                  `<div><input type="number" step="0.001" class="data-y" data-index="${index}" data-set="${setIndex}" value="${item}"></div>`;
              } else {
                dataUX +=
                  `<div><input type="number" step="0.001" class="data-x" data-index="${index}" data-set="${setIndex}" value="${
                    item.x
                  }"></div>` +
                  `<div><input type="number" step="0.001" class="data-y" data-index="${index}" data-set="${setIndex}" value="${
                    item.y
                  }"></div>`;
              }

              if (chartSettings.data.allow_bgc) {
                dataUX += `<div><input type="color" class="data-background" data-index="${index}" data-set="${setIndex}" value="${
                  backgroundColor[0]
                }"></div>`;
              }
              if (chartSettings.data.allow_bgo) {
                dataUX += `<div><input type="number" min="0" max="1.0" step="0.05" class="data-background-opacity" data-index="${index}" data-set="${setIndex}" value="${
                  backgroundColor[1]
                }"></div>`;
              }
              if (chartSettings.data.allow_brdc) {
                dataUX += `<div><input type="color" class="data-border" data-index="${index}" data-set="${setIndex}" value="${
                  borderColor[0]
                }"></div>`;
              }
              if (chartSettings.data.allow_brdo) {
                dataUX += `<div><input type="number" min="0" max="1.0" step="0.05" class="data-border-opacity" data-index="${index}" data-set="${setIndex}" value="${
                  borderColor[1]
                }"></div>`;
              }
              dataUX +=
                `<div><button class="button remove-data" data-set="${setIndex}" data-index="${index}">Remove data</button></div>` +
                `</div>`;
            });

            return dataUX;
          }

          function updateForm() {
            let dataUX =
              '<div class="data-ux"><details class="data"><summary>Data</summary>';
            if (chartSettings.data.allow_labels) {
              if (data.showlabels) {
                dataUX +=
                  `<div class="form-group"><label for="show-labels-${uid}">Show Labels</label><input class="show-labels" type="checkbox" id="show-labels-${uid}" checked="checked"></div>` +
                  `<div>` +
                  `<strong>Labels:</strong>` +
                  `<div class="data-labels-wrapper">`;

                let labelcount = 0;
                data.datasets.forEach(item => {
                  if (item.data.length > labelcount) {
                    labelcount = item.data.length;
                  }
                });
                for (let dataIndex = 0; dataIndex < labelcount; dataIndex++) {
                  dataUX += `<div class="form-group"><label for="labels-${dataIndex}-${uid}">${dataIndex +
                    1}:</label><input id="labels-${dataIndex}-${uid}" class="data-labels" data-index="${dataIndex}" value="${
                    data.labels[dataIndex]
                  }"></div>`;
                }
                dataUX += "</div></div>";
              } else {
                dataUX += `<div class="form-group"><label for="show-labels-${uid}">Show Labels</label><input class="show-labels" type="checkbox" id="show-labels-${uid}"></div>`;
              }
            }

            data.datasets.forEach((dataset, setIndex) => {
              dataUX +=
                `<div class="data-dataset" data-set="${setIndex}">` +
                `<div class="form-group"><label for="set-label-${setIndex}-${uid}">Dataset Label:</label><input id="set-label-${setIndex}-${uid}" type="text" class="data-label" data-set="${setIndex}" value="${
                  dataset.label
                }"></div>`;

              if (chartSettings.data.allow_brdw) {
                dataUX += `<div class="form-group"><label for="set-width-${setIndex}-${uid}">Border Width:</label><input id="set-width-${setIndex}-${uid}" type="number" class="data-border-width" data-set="${setIndex}" value="${
                  dataset.borderWidth
                }"></div>`;
              }

              if (chartSettings.data.allow_type) {
                dataUX += `<div class="form-group"><label for="set-type-${setIndex}-${uid}">Data Type:</label><select id="set-type-${setIndex}-${uid}" class="data-type">`;

                if (dataset.datatype === "value") {
                  dataUX +=
                    '<option value="value" selected>Value</option>' +
                    '<option value="point">Point</option>';
                } else {
                  dataUX +=
                    '<option value="value">Value</option>' +
                    '<option value="point" selected>Point</option>';
                }

                dataUX += "</select></div>";
              }

              dataUX +=
                '<div class="data-set-wrapper"><div class="table-header">';

              if (dataset.datatype === "value") {
                dataUX +=
                  "<strong></strong>" +
                  '<strong class="table-column">Value</strong>';
              } else {
                dataUX +=
                  '<strong class="table-column">X</strong>' +
                  '<strong class="table-column">Y</strong>';
              }

              if (chartSettings.data.allow_bgc) {
                dataUX +=
                  '<strong class="table-column">Background Color</strong>';
              }
              if (chartSettings.data.allow_bgo) {
                dataUX +=
                  '<strong class="table-column">Background Opacity</strong>';
              }
              if (chartSettings.data.allow_brdc) {
                dataUX += '<strong class="table-column">Border Color</strong>';
              }
              if (chartSettings.data.allow_brdo) {
                dataUX +=
                  '<strong class="table-column">Border Opacity</strong>';
              }
              dataUX += "</div>";

              dataUX += drawRow(dataset, setIndex);

              dataUX +=
                `${"</div>" +
                  '<button class="add-data button" data-set="'}${setIndex}" data-index="${
                  dataset.data.length
                }">Add data</button>` +
                `<button class="remove-dataset button" data-set="${setIndex}">Remove dataset</button>` +
                `</div>`;
            });

            dataUX +=
              '<button class="button add-dataset">Add dataset</button>' +
              "</details>" +
              '<details class="options"><summary>Options</summary>';

            if (optionsVal.title) {
              dataUX += `<div class="form-group"><label for="display-title-${uid}">Chart title:</label><input id="chat-title-${uid}" type="text" class="data-chart-title" placeholder="Chart Title" value="${
                optionsVal.title.text
              }"></div>`;
            } else {
              dataUX += `<div class="form-group"><label for="display-title-${uid}">Chart title:</label><input id="chat-title-${uid}" type="text" class="data-chart-title" placeholder="Chart Title"></div>`;
            }

            if (chartSettings.options.allow_display_title) {
              dataUX += '<div class="display-title">';
              if (optionsVal.title && optionsVal.title.display) {
                dataUX += `<div class="form-group"><label for="display-title-${uid}">Display title:</label><input id="display-title-${uid}" type="checkbox" class="data-display-title" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="display-title-${uid}">Display title:</label><input id="display-title-${uid}" type="checkbox" class="data-display-title"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_x_axis_display) {
              dataUX += "<div>";
              if (
                optionsVal.scales.xAxes[0].scaleLabel &&
                optionsVal.scales.xAxes[0].scaleLabel.display
              ) {
                dataUX += `<div class="form-group"><label for="x-axis-display-${uid}">Display X axis label:</label><input id="x-axis-display-${uid}" type="checkbox" class="data-x-axis-display" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-axis-display-${uid}">Display X axis label:</label><input id="x-axis-display-${uid}" type="checkbox" class="data-x-axis-display"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_x_axis_label) {
              dataUX += "<div>";
              if (
                optionsVal.scales.xAxes[0].scaleLabel &&
                optionsVal.scales.xAxes[0].scaleLabel.labelString
              ) {
                dataUX += `<div class="form-group"><label for="x-axis-label-${uid}">X axis label:</label><input id="x-axis-label-${uid}" type="text" class="data-x-axis-label" placeholder="X axis" value="${
                  optionsVal.scales.xAxes[0].scaleLabel.labelString
                }"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-axis-label-${uid}">X axis label:</label><input id="x-axis-label-${uid}" type="text" class="data-x-axis-label" placeholder="X axis"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_zero_x) {
              dataUX += '<div class="x-options">';
              if (optionsVal.scales.xAxes[0].ticks.beginAtZero) {
                dataUX += `<div class="form-group"><label for="x-axis-tick-zero-${uid}">X axis begin at zero:</label><input id="x-axis-tick-zero-${uid}" type="checkbox" class="data-x-zero" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-axis-tick-zero-${uid}">X axis begin at zero:</label><input id="x-axis-tick-zero-${uid}" type="checkbox" class="data-x-zero"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_x_min) {
              dataUX += "<div>";
              if (optionsVal.scales.xAxes[0].ticks.min) {
                dataUX += `<div class="form-group"><label for="x-min-${uid}">X axis minimum:</label><input id="x-min-${uid}" type="number" class="data-x-min" value=${
                  optionsVal.scales.xAxes[0].ticks.min
                }></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-min-${uid}">X axis minimum:</label><input id="x-min-${uid}" type="number" class="data-x-min"></div>`;
              }
              dataUX += "</div>";
            }
            if (chartSettings.options.allow_x_max) {
              dataUX += "<div>";
              if (optionsVal.scales.xAxes[0].ticks.max) {
                dataUX += `<div class="form-group"><label for="x-max-${uid}">X axis maximum:</label><input id="x-max-${uid}" type="number" class="data-x-max" value=${
                  optionsVal.scales.xAxes[0].ticks.max
                }></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-max-${uid}">X axis maximum:</label><input id="x-max-${uid}" type="number" class="data-x-max"></div>`;
              }
              dataUX += "</div>";
            }
            if (chartSettings.options.allow_x_step) {
              dataUX += "<div>";
              if (optionsVal.scales.xAxes[0].ticks.stepSize) {
                dataUX += `<div class="form-group"><label for="x-step-${uid}">X axis step size:</label><input id="x-step-${uid}" type="number" class="data-x-step" value=${
                  optionsVal.scales.xAxes[0].ticks.stepSize
                }></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-step-${uid}">X axis step size:</label><input id="x-step-${uid}" type="number" class="data-x-step"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_x_grid) {
              dataUX += "<div>";
              if (
                optionsVal.scales.xAxes[0].gridLines &&
                optionsVal.scales.xAxes[0].gridLines.display
              ) {
                dataUX += `<div class="form-group"><label for="x-axis-grid-${uid}">X axis grid:</label><input id="x-axis-grid-${uid}" type="checkbox" class="data-x-grid" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="x-axis-grid-${uid}">X axis grid:</label><input id="x-axis-grid-${uid}" type="checkbox" class="data-x-grid"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_y_axis_display) {
              dataUX += "<div>";
              if (
                optionsVal.scales.yAxes[0].scaleLabel &&
                optionsVal.scales.yAxes[0].scaleLabel.display
              ) {
                dataUX += `<div class="form-group"><label for="y-axis-display-${uid}">Display Y axis label:</label><input id="y-axis-display-${uid}" type="checkbox" class="data-y-axis-display" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-axis-display-${uid}">Display Y axis label:</label><input id="y-axis-display-${uid}" type="checkbox" class="data-y-axis-display"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_y_axis_label) {
              dataUX += "<div>";
              if (
                optionsVal.scales.yAxes[0].scaleLabel &&
                optionsVal.scales.yAxes[0].scaleLabel.labelString
              ) {
                dataUX += `<div class="form-group"><label for="y-axis-label-${uid}">Y axis label:</label><input id="y-axis-label-${uid}" type="text" class="data-y-axis-label" placeholder="Y axis" value="${
                  optionsVal.scales.yAxes[0].scaleLabel.labelString
                }"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-axis-label-${uid}">Y axis label:</label><input id="y-axis-label-${uid}" type="text" class="data-y-axis-label" placeholder="Y axis"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_zero_y) {
              dataUX += '<div class="y-options">';
              if (optionsVal.scales.yAxes[0].ticks.beginAtZero) {
                dataUX += `<div class="form-group"><label for="y-axis-tick-zero-${uid}">Y axis begin at zero:</label><input id="y-axis-tick-zero-${uid}" type="checkbox" class="data-y-zero" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-axis-tick-zero-${uid}">Y axis begin at zero:</label><input id="y-axis-tick-zero-${uid}" type="checkbox" class="data-y-zero"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_y_min) {
              dataUX += "<div>";
              if (optionsVal.scales.yAxes[0].ticks.min) {
                dataUX += `<div class="form-group"><label for="y-min-${uid}">Y axis minimum:</label><input id="y-min-${uid}" type="number" class="data-y-min" value=${
                  optionsVal.scales.yAxes[0].ticks.min
                }></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-min-${uid}">Y axis minimum:</label><input id="y-min-${uid}" type="number" class="data-y-min"></div>`;
              }
              dataUX += "</div>";
            }
            if (chartSettings.options.allow_y_max) {
              dataUX += "<div>";
              if (optionsVal.scales.yAxes[0].ticks.max) {
                dataUX += `<div class="form-group"><label for="y-max-${uid}">Y axis maximum:</label><input id="y-max-${uid}" type="number" class="data-y-max" value=${
                  optionsVal.scales.yAxes[0].ticks.max
                }></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-max-${uid}">Y axis maximum:</label><input id="y-max-${uid}" type="number" class="data-y-max"></div>`;
              }
              dataUX += "</div>";
            }
            if (chartSettings.options.allow_y_step) {
              dataUX += "<div>";
              if (optionsVal.scales.yAxes[0].ticks.stepSize) {
                dataUX += `<div class="form-group"><label for="y-step-${uid}">Y axis step size:</label><input id="y-step-${uid}" type="number" class="data-y-step" value=${
                  optionsVal.scales.yAxes[0].ticks.stepSize
                }></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-step-${uid}">Y axis step size:</label><input id="y-step-${uid}" type="number" class="data-y-step"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_y_grid) {
              dataUX += "<div>";
              if (
                optionsVal.scales.yAxes[0].gridLines &&
                optionsVal.scales.yAxes[0].gridLines.display
              ) {
                dataUX += `<div class="form-group"><label for="y-axis-grid-${uid}">Y axis grid:</label><input id="y-axis-grid-${uid}" type="checkbox" class="data-y-grid" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="y-axis-grid-${uid}">Y axis grid:</label><input id="y-axis-grid-${uid}" type="checkbox" class="data-y-grid"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_maintain_aspect_ratio) {
              dataUX += '<div class="aspect-ratio">';
              if (optionsVal.maintainAspectRatio) {
                dataUX += `<div class="form-group"><label for="maintain-aspect-ratio-${uid}">Maintain aspect ratio</label><input id="maintain-aspect-ratio-${uid}" type="checkbox" class="maintain-aspect-ratio" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="maintain-aspect-ratio-${uid}">Maintain aspect ratio:</label><input id="maintain-aspect-ratio-${uid}" type="checkbox" class="maintain-aspect-ratio"></div>`;
              }
              dataUX += "</div>";
            }

            if (chartSettings.options.allow_responsive) {
              dataUX += '<div class="aspect-ratio">';
              if (optionsVal.responsive) {
                dataUX += `<div class="form-group"><label for="responsive-${uid}">Responsive</label><input id="responsive-${uid}" type="checkbox" class="responsive-option" checked="checked"></div>`;
              } else {
                dataUX += `<div class="form-group"><label for="responsive-${uid}">Responsive</label><input id="responsive-${uid}" type="checkbox" class="responsive-option"></div>`;
              }
              dataUX += "</div>";
            }

            dataUX += "</details></div>";

            if (
              $(field)
                .find(".data-ux .data")
                .attr("open") === "open"
            ) {
              opendata = true;
            } else {
              opendata = false;
            }

            if (
              $(field)
                .find(".data-ux .options")
                .attr("open") === "open"
            ) {
              openoptions = true;
            } else {
              openoptions = false;
            }

            $(field)
              .find(".data-ux")
              .remove();
            $(field)
              .find(".chart-js-field-form-wrapper")
              .append(dataUX);
            if (opendata) {
              $(field)
                .find(".data-ux .data")
                .attr("open", "open");
            }
            if (openoptions) {
              $(field)
                .find(".data-ux .options")
                .attr("open", "open");
            }

            $(field)
              .find("input")
              .off()
              .on("input", () => {
                updateData();
              });

            $(field)
              .find("select")
              .not(".chart-type-select")
              .off()
              .on("change", () => {
                updateData();
                updateForm();
              });

            $(field)
              .find(".remove-data")
              .off()
              .on("click", function(e) {
                e.preventDefault();
                removeData($(this).data("set"), $(this).data("index"));
                updateForm();
              });

            $(field)
              .find(".remove-dataset")
              .off()
              .on("click", function(e) {
                e.preventDefault();
                removeDataset($(this).data("set"));
                updateForm();
              });

            $(field)
              .find(".add-data")
              .off()
              .on("click", function(e) {
                e.preventDefault();

                const set = $(this).data("set");
                const index = $(this).data("index");
                addData(set, index, data.datasets[set].datatype);
              });

            $(field)
              .find(".add-dataset")
              .off()
              .on("click", e => {
                e.preventDefault();
                addDataset();
                updateData();
                updateForm();
              });

            $(field)
              .find(".show-labels")
              .off()
              .on("input", () => {
                data.showlabels = !data.showlabels;
                updateData();
                updateForm();
              });
          }

          $(chartTypeField)
            .off()
            .on("change", () => {
              chartType = $(chartTypeField).val();

              if (
                $(field)
                  .find(".chart-preview")
                  .attr("open") === "open"
              ) {
                openchart = true;
              } else {
                openchart = false;
              }

              $(field)
                .find(".chart-preview")
                .remove();
              $(field).append(previewChart);
              if (openchart) {
                $(field)
                  .find(".chart-preview")
                  .attr("open", "open");
              }
              ctx = document.getElementById(`myChart-${uid}`).getContext("2d");
              myChart = new Chart(ctx, {
                type: $(field)
                  .find("select")
                  .val(),
                data,
                options: optionsVal
              });
            });

          updateForm();
        });
    }
  };
})(jQuery, Drupal, Chart);
