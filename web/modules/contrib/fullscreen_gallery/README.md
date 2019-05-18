INTRODUCTION
------------
The Fullscreen gallery module provides a new image field formatter for entity
types. This formatter extends default gallery display of image fields with
opening a Fullscreen gallery page while clicking on any image within gallery.

Features:

 * The images are always displayed in fullscreen mode (in largest available
   dimension determined by browser window)
 * Image thumbnails and image counter in Fullscreen gallery header line
 * Fullscreen gallery defines 6 own image styles to achieve the optimal
   bandwidth and image load speed. The module uses the nearest fitting image
   style based on the current browsers width
 * Automatic browser resize handling at runtime
 * Media querys for mobile and tablet displays
 * Optional: Display image titles (image title, image alt, or entity title)
 * Optional: Possibility to use a right sidebar for gallery, with fix or
   percentual width. The module creates new region for gallery right sidebar,
   so any blocks could be displayed in gallery page
 * You can use it easily for existing image fields


REQUIREMENTS
------------
No special requirements.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
[Installing modules]
(https://www.drupal.org/docs/8/extending-drupal-8/installing-module)
for further information.


CONFIGURATION
-------------
1. Go to "Configuration" > "Media" > "Fullscreen gallery settings" to find all
   the global configuration options for Fullscreen galleries:
   - Width of the right sidebar: The width in pixels or percent. Leave blank
     to right sidebar not appear.
   - Disable image titles: check to hide image titles in gallery view.
2. Browse to the "Manage Display" settings page for the entity type with
   an image field to set the Fullscreen gallery formatter. You can configure
   the image style for displaying images on entity view page. Furthermore you
   can override here default gallery settings for current field.
   For example:
   Structure » Content types » Basic page » Manage display
   (admin/structure/types/manage/page/display)
3. Configure user permissions in People » Permissions:
   - administer fullscreen gallery
     Users in roles with the "administer fullscreen gallery" permission will
     be able to modify Fullscreen gallery global configurations.


CUSTOMIZATION
-------------
 * To override the default gallery theme and page, you may redeclare the
   template files in your theme folder:
   - fullscreen-gallery.html.twig: for gallery theme
   - page-fullscreen-gallery.html.twig: for gallery page theme
 * To override Fullscreen gallery styles go to:
   "Configuration" > "Media" > "Image styles" and change styles with
   fullscreen_gallery prefix as you want.
 * To add more Fullscreen gallery styles simply create new styles with prefix
   fullscreen_gallery. The module automatically recognizes this styles
   and will use it for determining also the nearest fitting image.


FUTURE PLANS
------------
 * Add preloader for images.
 * Add option for ajax based image loading.
 * Add share and send to buttons into gallery header.
 * Add option for left sidebar.


MAINTAINERS
-----------
Current maintainers:
 * [Zoltan Bombicz (zbombicz)](https://drupal.org/user/325952)


OTHER
-----
For a full description of the module, visit the
[project page](https://drupal.org/project/fullscreen_gallery),

To submit bug reports and feature suggestions, or to track changes,
visit the
[issue queue](https://drupal.org/project/issues/fullscreen_gallery).
