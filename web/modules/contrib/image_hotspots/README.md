CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Instructions
 * Support
 * Maintainers

INTRODUCTION
------------
  Image Hotspots module allows you to mark the different areas of the image by
  text labels. Image must be provided by "Image" core module.
  
  Drupal 8 version works different from 7. Read the instructions section. 

REQUIREMENTS
------------
  This module requires the following modules:

  *  Image
  
INSTALLATION
------------
  Install as you would normally install a contributed Drupal module. See:
  https://drupal.org/documentation/install/modules-themes/modules-8
  for further information.
  
CONFIGURATION
-------------
 * Configure user permissions in "_Administration_ » _People_ » _Permissions_"
   (/admin/people/permissions)
     - Create and edit image hotspots
       Users in roles with the "Create and edit image hotspots" may create and edit image
       hotspots on existing images.
       
INSTRUCTIONS
------------
  *  Create content type (or other entities bundle) with image field.
  *  Go to your content type "Manage display" page.
  *  Change the formatter of this image field to "Image with Hotspots".
  *  Select image style.
  *  Create some content.
  *  Open the "view" page of your content.
  *  Under image now you can see "Add hotspot" button.
  
  Every hotspot is depend of field image style. So if you change it
  you will not see hotspots that was created with previous image style.

SUPPORT
-------
  Feel free to report bugs and propositions in our Issue Queue
  http://drupal.org/project/image_hotspots
  
MAINTAINERS
-----------
  This module developed by ADCI Solutions team.
    
  * https://www.drupal.org/adci-solutions
    
  * http://www.adcisolutions.com

