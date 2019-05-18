CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Pager module provides in-page navigation to the previous and next nodes.

 * For a full description of the module visit:
   https://www.drupal.org/project/pager

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/pager


Requirements
============
This module requires the following modules:
-------------------------------------------
- Block (core)
- Field (core)
- Filter (core)
- Node (core)
- System (core)
- Taxonomy (core)
- Text (core)
- User (core)
- Node (core)


INSTALLATION
------------

Install the Pager module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 - Previous Text: The text at the bottom of all previous links.
 - Next Text: The text at the bottom of all next links.
 - Image Field: The image filed to use for the block, (if navigation
   between content types is desired then it's convenient if they share an
   image field).
 - Image Style: The image style to use for the block. To get a consistant
   look, an image style that's scaled and cropped to specific aspect
   ration work best. To improve performance a size that matches display
   size, (or is close) works best.
 - Theme: There are currently two themes. A center block and side tabs.
 - Content Types: The content types that will be included in the this
   block's pagination. Pagination between content types is simplest if
   they share taxonomy and an image field.
 - Taxonomy Terms: The terms that will be included in pagination.
 - Maintain Term: If set the prev/next links will have the same taxonomy
   as the current content, (Dogs to Dogs and Cats to Cats). If not set
   then pagination will include any of the taxonomy terms selected,
   (Dogs to Dogs or Dogs to Cats or Cats to Honey Badgers).
 - Direction: Forward is cronoligical order from first to last, (first is
   oldest, last is newest).
 - End Behavior: The behavior at the first and last content.
    - Loop means the last content will feature a link to the first, (and
      vice versa).
    - Current means that when displaying the first or last content one
      pane will link to itself.
    - Single means that only one pagination link will appear, (next on
      first and prev on last).


MAINTAINERS
-----------

 * bkelly - https://www.drupal.org/u/bkelly
