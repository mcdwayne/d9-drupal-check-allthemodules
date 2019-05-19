The Taggd module integrates the Taggd javascript library with Drupal. 
Taggd allows you to add tooltips to your image.
That sounds way less exciting than it is, though...

## Installation

To install this module, do the following:

1. Download the [Taggd library]
   (https://github.com/timseverien/taggd) (version 3.x) 
   and place the resulting directory into the libraries directory. 
   Ensure `libraries/taggd/dist/taggd.min.js` and
   `libraries/taggd/dist/taggd.css` exist.
2. Download the Taggd module and follow the instruction for
      [installing contributed modules]
      (https://www.drupal.org/docs/8/extending-drupal-8/
      installing-contributed-modules-find-import-enable-configure-drupal-8).

## Usage

 1. Add new field of type "Taggd image" to your entity.
 2. Create new content and add one or more images.
 3. Click on the image to add a tag.
 4. Click on a tag to edit the label.
