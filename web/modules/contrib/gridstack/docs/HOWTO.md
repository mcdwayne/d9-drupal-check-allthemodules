***
***

## USAGE / CONFIGURATION
1. **As Views style plugin:**
   * Visit **/admin/structure/gridstack** to build a GridStack.
   * Visit **/admin/structure/views**, and create a new page or block with
     GridStack style, and assign the designated optionset.
   * Use the provided sample to begin with, be sure to read its README.md.

2. **As field formatters:**
   * Go to **Manage display** page, e.g.:
     **/admin/structure/types/manage/page/display**
   * Find **GridStack** formatters under **Manage display** for supported
     fields:
     Image, File entity reference, Media Entity, Paragraphs.
     With complex fields like Media Entity or Paragraphs, you can combine
     GridStack formatters with Slick formatters to build unique grid layout
     for the slider.

3. **As Bootstrap/ Foundation layouts:**
   * This requires any of _optional_ core Field Layout, Layout Builder, DS,
     Panelizer, or Widget modules, etc. to function.
   * Please refer to their documentations for better words.


## HOW TO
1. Visit GridStack UI to enable the static grid support:

   **/admin/structure/gridstack/ui**

   Choose either Bootstrap, or Foundation under **Grid framework**.
   Only one grid framework can exist globally.
   Be sure to fill out the required Grid library to make it work with core
   Layout Builder. It won't be loaded at front-end, only needed at admin pages,
   so be sure you have a theme, or module, which loads it at front-end.

2. Visit GridStack optionset collection page:

   **/admin/structure/gridstack**

   * Create, or clone existing **Default Bootstrap**, or **Default Foundation**.
     The required option: **Use static Grid Bootstrap/Foundation framework**.
     Disabled once saved to avoid issues.
   * Build static grid layout with floating boxes in mind, say
     **Boostrap Hero**.
     If a grid breaks, try putting them as nested grids within their own rows.
     + Leave **xl width** option empty if not desired, such as for Bootstrap 3.
       The **XL** grid class is only available at Bootstrap 4.
     + Clone existing optionsets to reduce steps.

3. Clear cache whenever updating/ editing existing layouts.
   * Don't always do this when all needed layouts are in place.

4. Assign the newly created layouts at any UI for Field, DS, Panels, Panelizer,
   Widget:
   * /admin/structure/types/manage/article/display
   * /admin/structure/panelizer/edit/node__page__default__default/layout?js=nojs
   * /admin/structure/block
   * Put relevant contents to each GridStack region.

5. Or visit **/admin/structure/views** to create Gridstack Views displays.


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


## TIPS
To have re-usable different image styles at multiple breakpoints, create them
once based on grid dimensions to easily match them based on the given dimension
hintings, e.g.:

* Box 1x1
* Box 1x2
* Box 2x1
* etc.

Enable GridStack UI and visit **/admin/structure/gridstack** and edit
your working optionset to assign different image styles for each box of the
GridStack. Then you can match those image styles with the provided dimension
hints easily -- 2x2 for Box 2x2, etc. Try giving fair sizes so that they fit
well and have no artifacts at multiple breakpoints.

Or use more simplified image styles like below:

* Box square
* Box tall
* Box tower
* Box wide
* Box panorama

And assign the largest dimensions to avoid artifact.
With **Use CSS background** option enabled, those sizes don't matter much.

Use Blazy formatters to have Colorbox, Photobox, Photoswipe, or other
lightbox supports.
