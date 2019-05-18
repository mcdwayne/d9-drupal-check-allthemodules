Outdated Browser module
------------------------------

DESCRIPTION
-----------

This module integrates the Outdated Browser library [1] in Drupal. It detects
outdated browsers and advises users to upgrade to a new version - in a very
pretty looking way.

The library ships with various languages. Its look and feel is configurable,
and the targeting browser can be configured either specifying a CSS property or
an Internet Explorer version.

More info at: http://outdatedbrowser.com


INSTALLATION
------------
1. Install as you would normally install a contributed drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.
2. Download the Outdated browser plugin from
   https://github.com/burocratik/outdated-browser with and extract the file
   under "libraries".
3. Ensure, that the js and css files, as well as the "lang" subdirectory are
   found within the following path:
   libraries/outdated-browser/outdatedbrowser/

INSTALLATION VIA COMPOSER
---------------------------
  It is assumed you are installing Drupal through Composer using the Drupal
  Composer facade. See https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#drupal-packagist

  The Outdated Browser JavaScript library does not support composer so manual
  steps are required in order to install it through this method.

  First, copy the repositories snippet from the composer.json file in this
  module into your project's composer.json file.

  Next, the following snippet must be added into your project's composer.json
  file so the javascript library is installed into the correct location:

  "extra": {
      "installer-paths": {
          "libraries/{$name}": ["type:drupal-library"]
      }
  }

  If there are already 'repositories' and/or 'extra' entries in the
  composer.json, merge these new entries with the already existing entries.

    After that, run:

    $ composer require burocratik/outdated-browser
    $ composer require drupal/outdatedbrowser

    The first uses the manual entries you made to install the JavaScript
    library, the second adds the Drupal module.

    Note: the requirement on the library is not in the module's composer.json
    because that would cause problems with automated testing.

  You should also use a composer script to remove the demo files in the library
  after composer install runs.
  See https://getcomposer.org/doc/articles/scripts.md

LIBRARIES API
-------------
Libraries API support has been removed from D8 version [2].

IE8 AND BELOW
-------------
Drupal 8 does not not support IE 8 and below anymore [3]. As a result of this,
a standard D8 installation will lead to Javascript errors on IE8 and below.
Being a JS-based solution, Outdated Browser won't be executed and shown at all.
So if you don't want to put some extra effort into making D8 running on IE8,
you'll need to rely on conditional comments instead. However, Outdated Browser
is still a good choice for excluding other browser, e.g. IE9 or based on a
certain CSS feature that your site needs to work correctly.

CREDITS
-------

The Outdated Browser module was originally developed and is currently
maintained by Mag. Andreas Mayr [4].

All initial development was sponsored by agoraDesign KG [5].


CONTACT
------------------------------------------------------------------------------

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/outdatedbrowser


References
------------------------------------------------------------------------------
1: https://github.com/burocratik/outdated-browser
2: http://www.agoradesign.at/blog/3rd-party-library-integration-drupal-8
3: https://www.drupal.org/node/1569578
4: https://www.drupal.org/u/agoradesign
5: http://www.agoradesign.at
