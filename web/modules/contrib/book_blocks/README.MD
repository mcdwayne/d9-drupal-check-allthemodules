CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Notes
 * Configuration


INTRODUCTION
------------

This module augments the built-in blocks for the Book module that is part
of Drupal core. The blocks can be used individually or in concert with
these blocks and the built-in blocks that are part of the Book module.
They all address the current book page being viewed and will not be displayed
if another content type is being viewed.

The blocks currently include:

    1. Children: list of child pages for the current page
    2. Navigation: Replicates the navigation links found at the bottom of the page.
    3. Table of Contents: Book's table of contents that also includes a link to
       the top page.
    4. Edit: Includes a combination of navigation, table of contents and edit
       links.

The Edit block is the one with the most options.

The Table of Contents can be used with the Collapsiblock module to expand and
hide the table of contents. This is built into the Edit block that does not
require that module.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/book_blocks

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/book_blocks


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Book Blocks module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


NOTES
-----

Edit Block Link Use:

 * The links (left, middle, right) in the Edit block can be used for any
   purpose.
   A typical use for the left link is included automatically and links to the
   list of books.
   The index is a View that is based off the {{ book_id }} so the path looks
   like:

   ```
    /book-index/{{ book_id }}
    ```

Panel Page Suggestion:

 * Use the blocks with the Panels project (http://drupal.org/project/panels)
   to define a page for book displays. This is done by adding a variant.
   The Selection Criteria has entity_bundle:node --  Content type is book
   The Content is:
     * Book edit links
     * Title
     * Body
     * Child pages
     * Book edit links

The titles are not displayed for any of the blocks.
I did have to add the following to one of my CSS files to get the title to match
the H1 definition.

``
.block-plugin-id--entity-field-nodetitle .field-name-title {
  margin-bottom: 1rem;
  text-transform: none;
  font-variant: normal;
  font-weight: 400;
  font-align: left;
  font-family: "Fira sans", sans-serif;
  font-size: 2.625rem;
  margin: 0 0 1rem;
  line-height: 1.125;
  box-sizing: border-box;
}
``
