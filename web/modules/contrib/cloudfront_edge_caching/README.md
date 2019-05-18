CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Cloudfront Edge Caching allows clear cache pages in Amazon Web Services 
through the Drupal Interface. Also, you can configure the module for automatic 
cache clear when users or content are updated.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/cloudfront_edge_caching

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/cloudfront_edge_caching

REQUIREMENTS
------------

This module requires the following SDK:

 * AWS SDK PHP (https://github.com/aws/aws-sdk-php)

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.
 * Remember install aws-sdk-php before enable the module. You can see the
   INSTALL.txt file for more information.

CONFIGURATION
-------------
 
 * Configure user permissions in Administration » People » Permissions:

   - Use the administration pages and help (System module)

     The top-level administration categories require this permission to be
     accessible. The administration menu will be empty unless this permission
     is granted.

   - Administer Cloudfront Edge Caching settings

     Users in roles with the "Administer Cloudfront Edge Caching settings" 
     permission will configure the settings parameters.

 * Customize the menu settings in Administration » Configuration » Services » 
   Cloudfront Edge Caching.

MAINTAINERS
-----------

Current maintainers:
 * José Antonio Rodríguez (jarodriguez) - https://www.drupal.org/user/1551452
