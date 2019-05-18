
Plotly.Js Module Readme
----------------------

<strong>Project URL</strong> - https://www.drupal.org/project/plotly_js

This module creates a new field type to create and render Plotly.js graphs
based solely on user form input and without the assistance of user-created
javascript.

Plotly.js can be found at https://plot.ly/javascript


Installation
------------

To install this module, place it in your modules folder and enable it on the
modules page.

The use of the Plotly.js requires the installation of the plotly.js library.
It can be downloaded from https://cdn.plot.ly/plotly-latest.min.js and should be
 installed in /libraries/plotly such that the file is located in
 /libaries/plotly/plotly-latest.min.js. Alternatively, you can enable use of
 a remote file for plotly at admin/config/content/plotly_js. You can also
 automatically install the plotly file using drush by issuing the command
 "drush plotly_js download".

After installation, a new field type of "Plotly.js Graph" will be available to
all entities. After adding this field to an entity, the Field Display Settings
will allow you to choose a graph type and modify all available display settings
for that graph type.

Configuration
-------------

Configuration for each graph is performed in the entity itself. After adding
a Plotly.js graph through the entity field settings, all configuration for the
graph is then done when adding content. Each configuration offers a description
of its purpose. For more information on configuration settings, see
https://plot.ly/javascript/reference

All graph settings are configured using YAML files made available in the
plotly_js module folder under "graph_templates". These graph templates can
be overridden to change the order, add, or remove fields. To override these
template files, specify a templates path at admin/config/content/plotly_js
and place a copy of the graph template files in the folder specified. Any
changes to the templates will then be reflected in the Drupal configuration.

Plotly.Js supports multiple template files to allow for different layouts.
Template files are found in the following order:
  PLOT_NAME.yml
  PLOT_NAME--ENTITY_TYPE.yml
  PLOT_NAME--ENTITY_TYPE--FIELD_MACHINE_NAME.yml
  PLOT_NAME--ENTITY_TYPE--ENTITY_ID.yml
  PLOT_NAME--ENTITY_TYPE--ENTITY_ID--FIELD_MACHINE_NAME.yml
So for example, 'scatter--node' would be used by all node scatter plots, but
'scatter--node--7' would be used for node 7 only and not by other nodes.

Maintainers
------

Daniel Moberly - <daniel.moberly@gmail.com>
