# Paragraphs for Indiana University

This module provides custom Paragraph types for building complex pages
comprised of elements from the [IU Pattern Library][1] like <em>section,
grids, and chunks</em>  that conform to the design standards in the
[IU Web Style Guide][1].

This module should be used in combination with the [IU Drupal Theme][3].

![Complex IU Chunk Layout](https://styleguide.iu.edu/images/greybox2.png)


# Supported widgets

Paragraph types currently available with this module include:

* Section - the top-level Paragraph type that you should add as a field on
    your content types; it supports background colors & images, parallax, as
    well as other display settings like height/width/padding, text alignment,
    and visibility rules, all from the standard IU Framework CSS.
* Text
* Image
* Carousel
* Call To Action
* Callouts & Pullquotes
* Accordion
* Stats
* Tabs - Not mentioned in the IU Pattern Library, but code was found in the IU
    Framework CSS for these. The implementation is based on Zurb Foundation
    Tabs and styling is completed via the [IU Drupal theme][3].
* Grid Buider - Build rows of equal-width <em>grid item</em> columns.
* Drupal Block - Embed a Drupal block inside a Paragraph.
* Drupal View - Embed a Drupal view in a Paragraph via viewsreference module.
* Form - Embeds a Drupal webform via the included iu_paragraphs_webform
    submodule.
* Banners - a top-level Paragraph type that may be added as a field in your
    content types; it can be used to place banner images, videos and text
    to the top of the page content.


# Roadmap

The following widgets still need to be implemented to completely support the
IU Framework:

* Feature
* Panels
* Image Essays
* Social Media Items
* Video & Audio


# Contrib Dependencies

* Field Group
* [IU theme][3]
* Paragraphs (with patch from Issue #2946856)
* ViewsReference
* SVG Image Field (dev version or patch from Issue #2920329)


# Installation

1) Download and enable the module and its contrib dependencies (see above).

2) Open _Admin > Structure > Content types > Basic Page > Manage fields_.

3) Add a field named _Sections_ of type _Paragraph_ (aka Entity Reference
    Revision); For _Reference Type_, include only the Section type;

4) Add a field named _Banner_ of type _Paragraph_ (aka Entity Reference
    Revision); For _Reference Type_, include only the Banner type;

5) We recommend the _Paragraphs Experimental_ field widget for Form Display.
    Note that the IU Paragraphs implements a nested Paragraphs architecture,
    so it is highly recommended to install the _Sortable.js_ library to
    leverage the advanced Drag & Drop feature in the Experimental widget
    because the basic tabledrag feature only allows you to reorder items
    within a single level. Follow Sortable.js installation instructions in
    the Paragraphs module's README.

6) Ensure your _Sections_ field and node type display is configured in an
    _Edge-to-edge_ layout. See _Layout settings_ on the _Manage display_ tab
    of your content type. (Field Layout and Layout Discovery modules must be
    enabled).  We recommend leveraging the _IU Page Layout_ from the IU theme
    for your field layouts.  This custom layout provides two regions:
    Constrained and Edge-to-edge.  Place the Body field in the Constrained
    region and the Sections field in the Edge-to-edge region.  An alternative
    approach would be to use the Page Manager module to handle field layouts,
    but Page Manager is outside the scope of this instruction manual.

7) Ensure your theme is configured to display the _Main page content_ in a
    region that spans the width of the screen (aka, edge-to-edge). We recommend
    using the _IU theme_  which contains appropriately labeled regions to
    support both edge-to-edge (for node pages) and constrained for non-node
    page content. The theme comes preloaded upon installation with two Main
    page content blocks;  one for node pages and one for non-node pages. The
    Main page content block for nodes is placed in the IU theme's Edge-to-edge
    region, while non-node Main page content block is in the standard Content
    region which has a constrained maximum width.

# Credits

* [Indiana University Libraries][4] - for sponsoring initial development.
* [Bluespark][5] - for sponsoring ongoing development.

Special thanks to [Paragraphs Pack][6] and [Bootstrap Paragraphs][7], both
of which provided the initial code from which this module was forked.

[1]: https://styleguide.iu.edu/pattern-library/ (IU Pattern Library)
[2]: https://styleguide.iu.edu/ (IU Style Guide)
[3]: https://drupal.org/project/iu (IU Drupal Theme)
[4]: https://libraries.indiana.edu (IU Libraries)
[5]: https://www.bluespark.com (Bluespark)
[6]: https://github.com/mishac/paragraphs_pack (Paragraphs Pack)
[7]: https://www.drupal.org/project/bootstrap_paragraphs (Bootstrap Paragraphs)
