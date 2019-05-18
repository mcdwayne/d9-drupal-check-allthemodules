# Installation

## Patch requirement

In order to properly use the license attribution form items with the inline 
media browser CKEditor button (the 'star' button), the following patch is required:

https://www.drupal.org/files/issues/paragraphs-missing-langcode-2901390-9.patch

From this Paragraphs issue: Integrity constraint violation: 1048 Column 'langcode' cannot be null 

https://www.drupal.org/project/paragraphs/issues/2901390

## Enabling the module

Install as you normally would. This module creates the following entity types:

  * Taxonomy vocabulary
    * Licences (media_attribution_licenses) - Includes License Link and License Icon fields
  * Paragraphs type
    * License Attribution (license_attribution)
    
# Use

The installation script automatically adds teh attribution paragraphs field
to the Image media type if it exists. This is installed by default
by the Lightning distribution.  Otherwise adding attribution 
can be one at Admin -> Structure -> Media Types. 

The attribution paragraph field has the following components

Source Work link, including a title and URL. This title is
usually the title of the original work.

Original Author link, including name and URL of the author's home page.

License type - optional, the license under which the linked work is released.

Free-form attribution text - optional, arbitrary text to clarify attribution. If no
license is selected, this text can be used to identify copyrighted works with e.g., 
"All rights reserved, used with permisssion."

This source and author info will be rendered below an embedded media object
when showing the node, following teh format preferred by Creative Commons,
outlined at Best Practices for Attribution:

https://wiki.creativecommons.org/wiki/best_practices_for_attribution

# Licenses

Installing the module creates License taxonomy term entries for all of the use
variations of the international Creative Commons licenses. These can be
edited, and other license types can be added as needed.
