
# ABOUT
GridStack provides integration with **gridstack.js** as a dynamic layout
builder for both magazine layout, and static float grid layout like Bootstrap,
or Foundation, with drag-and-drop. A few optional field formatters, and Views
style plugin.


## REQUIREMENTS
1. [Blazy module](http://dgo.to/blazy)
2. GridStack library:
   * [Download GridStack](https://github.com/gridstack/gridstack.js/releases)
   * Extract it as is, rename it to **gridstack**, so the assets are at:  
     + **/libraries/gridstack/dist/gridstack.min.js** (for js-driven front-end)
     + **/libraries/gridstack/dist/gridstack.all.js** (required by admin UI)

### Note:
* GridStack 8.x-1.0-beta4 below requires v0.2.5 (deprecated).
* GridStack 8.x-2.x and DEV versions require a minimum v0.4.0+.


## INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)


## MODULES WHICH REQUIRE GRIDSTACK
* [Outlayer](http://dgo.to/outlayer)

  **Brains and guts of a layout library**. Integrates Outlayer for layout
  libraries like Isotope, Masonry, Packery with Blazy and GridStack. Outlayer
  will make awesome GridStack layouts or custom-defined grids filterable,
  sortable, searchable.


## FEATURES
* Supports magazine layouts, identified by fixed heights.
* Supports static float layouts for Bootstrap, or Foundation, identified by
  auto heights. With Bootstrap 4 flexbox grid, limited magazine layout is
  possible with little CSS overrides.
* Supports optional core Layout Builder, DS, Panelizer, Widget modules.
* Responsive grid displays, layout composition, image styles, or multiple unique
  image styles per grid/box.
* Drag and drop layout builder.
* Lazyloaded inline images, or CSS background images with multi-styled images.
* Field formatters for Image, and fieldable entities like File Entity Reference,
  and core Media and Paragraphs integration. Specific to fieldable
  entities, best when containing core image using Blazy formatters with CSS
  background option enabled. Requires Blazy post Beta5+.
* Optional Views style plugin.
* Optional supports for Colorbox, Photobox, Blazy Photoswip, or any lightbox
  supported by Blazy when using Blazy Views fields, or Blazy-related formatters
  from within Views.
* Modular and extensible skins.
* Easy captioning.
* Rich boxes (via **block_field.module**) to alternate boring images with a mix
  of rich boxes: Slick carousel, video, any potential block_field: currency,
  time, weather, ads, donations blocks, etc.
* Stamps (via Views **HTML list**)
* A few simple box layouts.
* Both layout flavors are usable for Display Suite, Panels, Layout Builder, etc.
* Region or wrapper attributes, including their HTML tags, are configurable via
  Layout builder for both layout flavors.


## SIMILAR MODULES
[Mason](http://dgo.to/mason)

Both try to solve one problem: empty gaps within a compact grid layout.
Mason uses auto Fillers, or manual Promoted, options to fill in empty gaps.
GridStack uses manual drag and drop layout builder to fill in empty gaps.


## CURRENT DEVELOPMENT STATUS
A full release should be reasonable after proper feedbacks from the community,
some code cleanup, and optimization where needed. Patches are very much welcome.

Alpha and Beta releases are for developers only. Be aware of possible breakage.

However if it is broken, unless an update is explicitly required, clearing cache
should fix most issues during DEV phases. Prior to any update, open:

**/admin/config/development/performance**

Re-generate CSS and JS assets, and hit **Clear all caches** button.


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/2672858/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## THIRD PARTY MATERIALS
**gridstack.js**

The version of gridstack.js included in this project is OPTIONAL, a derived from
gridstack.js 0.3.0-dev

https://troolee.github.io/gridstack.js/

(c) 2014-2016 Pavel Reznikov, Dylan Weiss.
It was licensed under the MIT license.

It has been modified by Gaus Surahman to work with Drupal.
The modified version removed deprecated methods worth ~20Kb minified (half),
suitable only for static grid option as normally seen at static frontend.

**gridstack.css**

The version of gridstack.css included in this project is a derived from
gridstack.css 0.3.0-dev

https://troolee.github.io/gridstack.js/

(c) 2014-2016 Pavel Reznikov, Dylan Weiss.
It was licensed under the MIT license.

It has been modified by Gaus Surahman to work with Drupal.
The modified version provided modularity which is not accommodated by
the original file.


## READ MORE
See the project page on drupal.org:

[Gridstack module](https://drupal.org/project/gridstack)

See the GridStack JS docs at:

* [Gridstack at Github](https://github.com/troolee/gridstack.js)
* [Gridstack website](http://troolee.github.io/gridstack.js/)
