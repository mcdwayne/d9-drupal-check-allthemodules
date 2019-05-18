CONTENTS OF THIS FILE
---------------------

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Maintainers



INTRODUCTION
------------

  Hard refresh the page when someone click on browser back button

    * When we click browser back button then browser loads page from browser
      history.
    * Suppose someone opens login page and enters login credentials.
    * After login click on browser back button, you can see again log in screen
      but you are actually logged in.
    * This module hard refresh the page on browser back button event so that
      you can see logged in user page instead of anonymous user page.

REQUIREMENTS
------------

  This module does not have any dependencies.


INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. See:
    https://drupal.org/documentation/install/modules-themes/modules-7
    for further information.

CONFIGURATION
-------------

  * Configure Back Button Refresh block visibility settings in
    Home » Administration » Structure » Blocks:

    Drupal 8
    --------
    - Click to "Place Block" link beside the region name. You can place Back
      Button Refresh block to any active region in your theme.
    - Set visibility of block to display on all pages.
    - If you are using multiple themes in your projects then do place this Block
      to your all themes so that only people have right permission can view the
      page.

    Drupal 7
    --------
    - Search Back Button Refresh block and set it to bottom region of your
      default theme.
    - Set visibility of block to display on all pages.
    - If you are using multiple themes in your projects then do place this Block
      to your all themes so that only people have right permission can view the
      page.  

MAINTAINERS
-----------

  Current maintainers:
    * Yogesh Kushwaha - https://www.drupal.org/u/yogeshkushwaha89
