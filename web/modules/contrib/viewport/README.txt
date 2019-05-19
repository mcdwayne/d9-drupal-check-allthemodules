CONTENTS OF THIS FILE
---------------------

 * INTRODUCTION
 * INSTALLATION
 * USAGE


INTRODUCTION
------------

Current Maintainer: Salvador Molina <salvador.molinamoreno@codeenigma.com>

The Viewport module is a simple module that allows administrators, or users
with the "Administer Viewport Settings" permission, to set a Viewport HTML
metatag with the desired properties for one or several pages that can be
configured from the Settings page of the module.

The aim of the module is to provide an easy way to debug or test websites or
apps, as well as responsive designs on smartphones and tablets.

Sometimes, one may need to set specific viewport values for a specific page on
the site (e.g when embedding a game for smartphones / tablets). This tool
helps to solve easily situations like that.

INSTALLATION
------------

To install the Viewport module:

 1. Place its entire folder into the "modules" folder of your
    drupal installation.

 2. In your Drupal site, navigate to "admin/modules", search the "Viewport"
    module, and enable it by clicking on the checkbox located next to it.

 3. Click on "Save configuration".

 4. Enjoy.

USAGE
-----

After installing the module:

  1. Navigate to "admin/people/permissions" and assign
     the "Administer Viewport Settings" permission to the desired roles.

  2. Navigate to "admin/appearance/settings/viewport", and set the desired
     values for the different viewport properties.

  3. In the textarea provided, enter the paths (one per line) for which you
     want the viewport tag to appear. Note in Drupal 8 paths are preceded by a
     forward slash "/".

  4. For more information on the Viewport properties and their meanings,
     navigate to "admin/help/viewport".
