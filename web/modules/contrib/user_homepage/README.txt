CONTENTS OF THIS FILE
---------------------

 * INTRODUCTION
 * INSTALLATION
 * USAGE

INTRODUCTION
------------

Written by: Salvador Molina <salvador.molinamoreno@codeenigma.com>

The User Homepage module lets users with the 'configure own homepage' permission
choose a specific page of the site as their homepage. Users with a homepage will
be redirected to this page upon successful login on the site.

The module provides two blocks. One of them allows users to save the current
page as their homepage, and the other allows them to reset their homepage so
that they are no longer redirected after login in.

Note that if there is a 'destination' parameter in the login request, the
homepage redirect is not triggered, and the user is taken to the path specified
in the 'destination' parameter.

INSTALLATION
------------

To install the User Homepage module:

 1. Place its entire folder into the "modules" folder of your drupal
    installation.

 2. In your Drupal site, navigate to "admin/modules", search the "User Homepage"
    module, and enable it by clicking on the checkbox located next to it.

 3. Click on "Save configuration".

 4. Enjoy.

USAGE
-----

 1. Assign the 'Configure own homepage' permission to the desired roles.

 2. Navigate to "admin/structure/block" and assign the "Save as homepage button"
    and "Reset homepage button" blocks in the theme regions where you want them
    to appear.
