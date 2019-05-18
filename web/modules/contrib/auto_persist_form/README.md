Auto persist data form
=======

This module allows you to automatically persist your forms' text field values locally, until the form is submitted. This way, your users don't lose any precious data if they accidentally close their tab or browser.

Installation
First you create new folder either in the sites/all/modules folder or just directly in the modules folder at the Drupal root. The good news is that you can move the folder even after, enabled. No more need to rebuild the registry. You can thanks the clever autoloading capability of Drupal 8.

Requirements
Download the Garlic.js, rename the folder as 'garlicjs' and place it under your libraries folder. So your file structure should look like this: [drupal_root]/libraries/garlicjs/garlic.js