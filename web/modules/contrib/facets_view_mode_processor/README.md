This module provides a facets processor to render facet entity reference items as view modes

## Installation

To install this module, do the following:

1. Download the Facets view mode processor module and follow the instruction for
      [installing contributed modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8).

## Usage

 1. Install module
 2. Create a facet based upon an entity reference field (for example Taxonomy term)
 3. Navigate to the processors of the facet and check "Transform entity ID to view mode"
 4. Choose the view mode to render the items in
 
__Note__: Make sure the HTML rendered in the view mode is valid HTML to be used with a checkbox element.
Not all HTML is allowed. I suggest you override the template of your chosen view mode to take full control on 
how each facet item is rendered.