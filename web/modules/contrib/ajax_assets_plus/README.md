AJAX assets plus
================

The module provides additional functionality for handling css and javascript
assets in ajax requests. It adds all the necessary ajax commands and libraries
to the render array.

Main benefits of the module:
- Cacheable **Ajax GET requests**.
- Ability to resolve assets when transferring requests using Views REST export
  or some middleware(e.g. web-sockets).

Example of usage
----------------
Use the ajax_assets_plus_example submodule as an example of usage. You can find the
test controller and javascript there. Also see the [/tests](http://cgit.drupalcode.org/ajax_assets_plus/tree/tests/src/Functional?h=8.x-1.x) folder.

Running javascript tests
------------------------
To run the javascript tests the `PhantomJS` is required.

Instructions:
1. Install [PhantomJS](http://phantomjs.org/download.html) on your computer.
1. Start PhantomJS browser in the root folder of your Drupal 8 checkout:
   ```
   /path/to/phantomjs --ssl-protocol=any --ignore-ssl-errors=true vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1024 768
   ```
1. Start tests using PHPUnit.

Prerequisites
-------------

**Attention:** For views integration the Drupal core patch to fix [Empty request in ViewExecutable::unserialize() on a cached ajax request](https://www.drupal.org/node/2895584#comment-12172734)
is required.
