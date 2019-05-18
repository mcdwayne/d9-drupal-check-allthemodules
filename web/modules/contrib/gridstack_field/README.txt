INTRODUCTION
------------

This module implements Gridstack plugin as field. User can create custom
configurable grid layout and display in this grid different nodes.
This can help to create "uncommon" landing pages.

See the GridStack JS docs at:
o https://github.com/troolee/gridstack.js
o http://troolee.github.io/gridstack.js/

In this field we can configure which content types can be added and showed in
this field. Than in field settings can be chosen settings for gridstack
plugin. After this manipulations users on the site can create pages with themed
grid of chosen nodes.

This module uses Backbone and Underscore plugins
for creating structure of field.


REQUIREMENTS
------------

 * Libraries API (https://www.drupal.org/project/libraries)
 * Backbone (http://backbonejs.org/)
 * Underscore (http://underscorejs.org/)
 * Gridstack plugin (https://github.com/troolee/gridstack.js)


INSTALLATION
------------

For installing module needs to download next libs and put them into
related sites/all/libraries(in Drupal 8 you need
to create all folders from path above) directories on the site:
 * Gridstack plugin
    (https://github.com/troolee/gridstack.js/blob/master/dist/gridstack.min.css
    https://github.com/troolee/gridstack.js/blob/master/dist/gridstack.min.js
    https://github.com/troolee/gridstack.js/blob/master/dist/gridstack.jQueryUI.min.js
    https://github.com/troolee/gridstack.js/blob/master/dist/gridstack.min.map)
    - sites/all/libraries/gridstack


USES
----

Adding Gridstack field
  Can be added on content type configuring page on Manage fields tab
  (admin/structure/types/manage/%/fields).
  For new field need to choose field type property as "Gridstack field"
  and click save button.



CONFIGURATION
-------------

Configuring field
  Field can be configured on standart field edit page
  (admin/structure/types/manage/grid/fields/%field_name).
  On this page user can choose which content type can be added/displayed
  in our field.

Gridstack plugin configuration
  Gridstack options can be configured on field sttings form
  (admin/structure/types/manage/grid/fields/%field_name),
  there are next parameters:
    - Height - maximum rows amount. Default is 0 which means no maximum rows.
    - Width - amount of columns (default: 12).
    - Cell height - one cell height (default: 60).
    - Min width - minimal width. If window width is less,
      grid will be shown in one-column mode.
      You need also update your css file (@media (max-width: 768px) {...})
      with corresponding value (default: 768).
    - RTL - if true turns grid to RTL. Possible values are true, false,
      "auto" (default: "auto").
    - Vertical margin - vertical gap size (default: 20).
    - Animate - turns animation on (default: false).
    - Always show resize handle - if true the resizing handles are shown
      even if the user is not hovering over the widget (default: false).
    - Auto - if false gridstack will not initialize existing items
      (default: true).
    - Disable drag - disallows dragging of widgets (default: false).
    - Disable resize - disallows resizing of widgets (default: false).
    - float - enable floating widgets (default: false).


MAINTAINERS
-----------

Current maintainers:
 * Misha Humeniuk (GrapefruitJuice) - https://www.drupal.org/u/grapefruitjuice
 * Irina Chobotan (ipo4ka704) - https://www.drupal.org/u/ipo4ka704
