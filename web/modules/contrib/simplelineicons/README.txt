CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Credits


INTRODUCTION
------------

Simple Line Icons (http://simplelineicons.com/) is a free iconic font.

"simplelineicons" provides integration of "Simple Line Icons" with Drupal.
Once enabled "Simple Line Icons" icon fonts could be used as:

1. Directly inside of any HTML (node/block/view/panel). Inside HTML you can
   place "Simple Line Icons" icons just about anywhere with an <i> tag.

   Example for an info icon: <i class="icon-social-twitter"></i>

   See more examples of using "Simple Line Icons" within HTML at:
   http://simplelineicons.com/

2. Icon API (https://drupal.org/project/icon) integration:
   This module provides easy to use interfaces that quickly allow you to inject
   icons in various aspects of your Drupal site: blocks, menus, fields, filters.


INSTALLATION
------------

1. Using Drush (https://github.com/drush-ops/drush#readme)

    $ drush pm-enable simplelineicons

    Upon enabling, this will also attempt to download and install the library
    in `/libraries/simple-line-icons`. If, for whatever reason, this process
    fails, you can re-run the library install manually by first clearing Drush
    caches:

    $ drush cc drush

    and then using another drush command:

    $ drush sli-download

2. Manually

    a. Install the "Simple Line Icons" library following one of these 2
       options:
       - run `drush sli-download` (recommended, it will download the right
         package and extract it at the right place for you.)
       - manual install: Download & extract "Simple Line Icons"
         (http://simplelineicons.com) and place inside
         `/libraries/simple-line-icons` directory. The CSS file should
         be `/libraries/simple-line-icons/css/simple-line-icons.css`
         Direct link for downloading latest version (current is v2.4.1) is:
         https://github.com/thesabbir/simple-line-icons/archive/2.4.1.zip
    b. Enable the module at Administer >> Site building >> Modules.

CREDITS
-------

* Sándor Juhász (lonalore) https://www.drupal.org/user
