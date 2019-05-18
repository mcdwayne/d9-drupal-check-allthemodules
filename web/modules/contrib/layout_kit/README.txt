
Layout Kit is a ready to use set of layouts, now (take a look at screenshots):
  - Accordion.
  - Conmutator (accordions where all the sections can be closed/opened at once).
  - Tabs: horizontal.
  - Tabs: vertical.

Each layout has been built with its library using jQuery and just the minimum
required styles and js.
That means you won't need Bootstrap (or any other heavy framework) to build
these kind of components, you can use any theme, even bartik or Stark and these
components will be shown perfectly.
And if you are a theming expert; you'll be able to replace library entirely if
you need to, or just its css file, or js using another awesome JS library to
build element.
Also you won't be forced to struggle with a set of infinite css definitions,
this only has the minimum to work (check css files).
All selectors inside templates are BEM @TODO based.

These layouts has been tested with:
  - Field Layout: You will be able to build entity view mode and show its fields
    as an accordion for example.
  - Field Group: This is useful if you want to group fields inside first
    accordion element for example.
  - Bricks: highly recommended to unleash the real power of this, you will be
    able to create 'layout' entity and render each item inside as an accordion
    element for example.

IMPORTANT:
These patches/module versions are required in order to have everything working
(just if you are using related features):
  - Field Group 8.x-3.x-dev: "Field groups are not compatible with field
    layout".
    https://www.drupal.org/project/field_group/issues/2878359 - Solved into
    version: 8.x-3.x-dev.

  - Bricks 8.x-1.x-dev: Bricks trims the number of items to the number of
    regions of layout, that scenario has no sense if you can have unlimited
    items, better use just one region and place all items inside, let layout do
    its magic (as field layout does).
    Make layout region clearer: items above number of regions just vanish:
    https://www.drupal.org/project/bricks/issues/2886616
    https://www.drupal.org
    /files/issues/2018-07-29/bricks-layout_build_items-2886616-9-8.x.patch

Here goes some examples to use:
Field Layout and Field Group (optional):
Configurantion:
https://www.drupal.org/files/layout_kit_field_layout_field_group_config.png
Result:
https://www.drupal.org/files/layout_kit_field_layout_field_group_result.png

Layout Kit + Bricks:
Edit:
https://www.drupal.org/files/layout_kit_bricks_result_edit.png
Result:
https://www.drupal.org/files/layout_kit_bricks_result.png

Layout Kit + Bricks + Nested:
Edit:
https://www.drupal.org/files/layout_kit_bricks_nested_edit.png
Result:
https://www.drupal.org/files/layout_kit_bricks_nested_result.png

Layout Kit + Field Layout + Field Group*:
Edit:
https://www.drupal.org/files/layout_kit_field_layout_field_group_config.png
Result:
https://www.drupal.org/files/layout_kit_field_layout_field_group_result.png


*IMPORTANT: In order to hide field group label but show as layout element title:
select "Show label: Yes" and empty field "Label element".
https://www.drupal.org/files/layout_kit_field_group_title_config.jpg
If you select "Show label: NO", title won't be shown. Have that into account.

Related useful module:
Field Group Label: allows you to customize field group label differently for
each node or entity where it's used.
https://www.drupal.org/project/field_group_label
