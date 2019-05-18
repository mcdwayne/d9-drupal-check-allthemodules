
# Outlayer
Brains and guts of a layout library.

Integrates Outlayer for layout libraries like Isotope, Masonry, Packery with
Blazy and GridStack. Outlayer will make awesome GridStack layouts or simpler
custom-defined grids filterable, sortable, searchable.


## REQUIREMENTS
1. [Gridstack 2.x](http://dgo.to/gridstack)
2. [Isotope](https://github.com/metafizzy/isotope):
   * Extract it as is, rename it to **isotope**, so the assets are at:

     + **/libraries/isotope/dist/isotope.pkgd.min.js**

     If using Composer it will be:
     + **/libraries/isotope-layout/dist/isotope.pkgd.min.js**

     Both are supported.
3. [Imagesloaded](https://github.com/desandro/imagesloaded):
   * Extract it as is, rename it to **imagesloaded**, so the assets are at:

     + **/libraries/imagesloaded/imagesloaded.pkgd.min.js**


## OPTIONAL INTEGRATION     
1. [Packery](https://github.com/metafizzy/packery):
   * Extract it as is, rename it to **packery**, so the assets are at:  
     + **/libraries/packery/dist/packery.pkgd.min.js**

     If using Composer it will be:
     + **/libraries/packery-layout/dist/packery.pkgd.min.js**

     Both are supported.
2. [Masonry](https://github.com/desandro/masonry):
   * Extract it as is, rename it to **masonry**, so the assets are at:  
     + **/libraries/masonry/dist/masonry.pkgd.min.js**

     If using Composer it will be:
     + **/libraries/masonry-layout/dist/masonry.pkgd.min.js**

     Both are supported.
3. Other Isotope, Masonry, Packery related-layout modes:
   * [masonry-horizontal](https://github.com/metafizzy/isotope-masonry-horizontal)
   * [horizontal](https://github.com/metafizzy/isotope-horizontal)
   * [fit-columns](https://github.com/metafizzy/isotope-fit-columns)
   * [cells-by-column](https://github.com/metafizzy/isotope-cells-by-column)
   * [cells-by-row](https://github.com/metafizzy/isotope-cells-by-row)
   * [isotope-packery](https://github.com/metafizzy/isotope-packery)

   If you download it directly from Github, not via Composer, be sure to remove
   **-master** suffix from the folder name, e.g.:
   **isotope-packery-master** becomes **isotope-packery**

   Not all is needed. You may want one of the above which fits your need.
   However to avoid broken displays, simply download them all. Only the required
   one will be used on the page, anyway.

Use Composer to download them all at ease. If things are broken, be sure the
library is installed correctly.


## INSTALLATION
Install the module as usual, more info can be found on:

[Installing Drupal 8 Modules](https://drupal.org/node/1897420)


## USAGE / CONFIGURATION
1. As Views style plugin:
   * Visit **/admin/structure/gridstack** to build a Gridstack optionset.
   * Visit **/admin/structure/outlayer** to build an Outlayer optionset.
   * Visit **/admin/structure/views**:
     + Add a new page or block with Outlayer Isotope, Outlayer Filter, or
       Outlayer Sorter styles on the same view.
     + Associate one to another via the provided options, so they can work
       together.
     + Put the 3 blocks on the same page at **/admin/structure/block**

Use the provided sample to begin with.

### RICH BOXES
Replace **Main stage/ image** if a node has one. It can be any entity reference,
like Block, etc. Use **block_field.module** for ease of block additions.

#### HOW TO RICH BOXES
1. Create a sticky (or far future created) node or two containing a Slick
   carousel, video, weather, time, donations, currency, ads, or anything as a
   block field.
2. Put it on the **Rich boxes** option.
   Be sure different from the **Main stage**.

While regular boxes are updated with new contents, these rich boxes may stay
the same, and sticky (Use Views with sort by sticky or desc by creation).

### STAMPS
Stamp is _just_ a unique list, **Html list**, such as Latest news, blogs,
testimonials, etc. replacing one of the other boring boxes, including rich ones.

Read more about the usage instruction at GridStack module.


## FEATURES
* A few Views style plugins:
  + Outlayer Isotope.
  + Outlayer Isotope Filter with Search filter.
  + Outlayer Isotope Sorter.
  + Outlayer Grid for Masonry and Packery
* Rich boxes (via **block_field.module**)
* Stamps (via Views **HTML list**)
* Supports both GridStack or custom defined irregular grids.
  If you need regular grid sizes, consider **Blazy Grid** instead.


## RECOMMENDED
1. Block field module to have the rich boxes.


## ROADMAP
[x] Isotope (Packery and Masonry) integration with GridStack.
    02/27/2019

[x] Masonry integration with Blazy.
    03/17/2019

[x] Packery integration with Blazy or GridStack.
    03/17/2019

[?] Formatters.

[?] Build libraries from the sources as the above packages contain dups.

Feel free to get in touch if you'd like to chip in, or sponsor any. Thanks.

## EXAMPLE
The Outlayer sample requires a **field_image** normally at Article content type
and automatically created when using Standard profile.

If no **field_image**, simply create a field named **Image**.

Adjust the provided views:

**/admin/structure/views/view/outlayer_x/edit**


## TROUBLESHOOTING/ KNOWN ISSUES
1. Grids may have weird aspect ratio with margins.
   **Solutions**:
   * `Vertical margin` = 0
   * `No horizontal margin` enabled
   * Adjust `Cell height`
2. To have gapless grids, be sure to have the amount of contents similar to the
   amount of grid items **minus** stamp, if provided.
   The same rule applies to either GridStack or custom-defined grids.
   This is easily fixed via Views UI under **Pager**.
3. Update to the latest GridStack along with this module update for
   compatibility issues.


## TIPS
To have a similar Tagore GridStack with **Outlayer Grid** style without using
GridStack, try below. Use a little math to have gapless grids.

* For **Grid Custom**, add:

  4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2

* Add a stamp at index 3.
* **Layout**, choose Packery.
* **Outlayer optionset**, choose Packery.
* Limit Views result to 8 items under **Pager**.
* Enable **Use CSS background**.

Adjust CSS accordingly as the above is not really responsive.

To have regular grid sizes, simply input your aspect ratio once, e.g.:
  2x2 or 4x2, etc.


## LICENSES
Check out Commercial licenses before usage:

* [Masonry license](http://masonry.metafizzy.co/license.html)
* [Packery license](http://packery.metafizzy.co/license.html)
* [Isotope license](http://isotope.metafizzy.co/license.html)


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/3036970/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

[Outlayer module](https://drupal.org/project/outlayer)

See the Outlayer JS and its related-layout docs at:

* [Outlayer at Github](https://github.com/metafizzy/outlayer)
* [Isotope website](https://isotope.metafizzy.co/)
* [Isotope at Github](https://github.com/metafizzy/isotope)
* [Masonry website](https://masonry.desandro.com/)
* [Masonry at Github](https://github.com/desandro/masonry)
* [Packery website](https://packery.metafizzy.co/)
* [Packery at Github](https://github.com/metafizzy/packery)
