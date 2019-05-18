# ChartJS API

## Introduction

Integration API with the ChartJS library that provides a "render element"
for generating graphs.

In addition to the standard graphs of GraphJS, a new type called "halfdonut"
is added. With this, from a doughnut type graph, "half doughnut" type graphs
can be generated.


## Installation and configuration

ChartJS API Plugin can be installed like any other Drupal module.
Place it in the modules directory for your site and enable it
on the `admin/modules` page.

This module use lastest version of ChartJS.js library from CDN.

Parameters for "render element" array:
- data: Array with labels and datasets with same structure
than the ChartJS.js library.
- graph_type: Chart type (line, bar, radar, pie... and halfdonut).
- id: Unique id for chart.
- options: Array with options of ChartJS.js library, with same structure.
- plugins: Plugins to activate. This module include halfdonutTotal plugin.
    This plugin display the title inside half doughnut.
- type: chartjs_api

You can find more info in ChartJs documentation:
http://www.chartjs.org/docs/latest/

Example of use:
# Bar graph.
$build['mychart'] = [
  '#data' => [
    'labels' => ['January', 'February', 'March'],
    'datasets' => [
      [
        'label' => 'Dataset 1',
        'data' => [180, 500, 300],
        'backgroundColor' => ['#00557f', '#00557f', '#00557f'],
        'hoverBackgroundColor' => ['#004060', '#004060', '#004060'],
      ],
      [
        'label' => 'Dataset 2',
        'data' => [200, 180, 400],
        'backgroundColor' => ['#f8413c', '#f8413c', '#f8413c'],
        'hoverBackgroundColor' => ['#9b2926', '#9b2926', '#9b2926'],
      ],
    ],
  ],
  '#graph_type' => 'bar',
  '#id' => 'mychart',
  '#type' => 'chartjs_api',
];

# Half doughnut graph.
$build['mychart'] = [
  '#data' => [
    'labels' => ['Blue', 'Red', 'Grey'],
    'datasets' => [
      [
        'label' => 'Dataset 1',
        'data' => [180, 500, 300],
        'backgroundColor' => ['#00557f', '#f8413c', '#666666'],
        'hoverBackgroundColor' => ['#004060', '#9b2926', '#333333'],
      ],
    ],
  ],
  '#graph_type' => 'halfdonut',
  '#id' => 'mychart',
  '#options' => [
    'title' => [
      'text' => 980,
    ],
  ],
  '#plugins' => ['halfdonutTotal'],
  '#type' => 'chartjs_api',
];
