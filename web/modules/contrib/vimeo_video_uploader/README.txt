CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Prerequisite
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Vimeo Video Uploader module allows users to Uploads videos to vimeo on
creation of content from your drupal site.

Project homepage: http://drupal.org/project/vimeo_video_uploader


REQUIREMENTS
------------

This module requires the following modules:

 * Video embed field (https://www.drupal.org/project/video_embed_field)
 * Libraries (https://www.drupal.org/project/libraries)


PREREQUISITE
------------

 * Create a App at vimeo.com, visit <https://developer.vimeo.com/apps>
   Get the Created App Authenticated.
   (It takes few days to get authenticated by vimeo.com)
   Copy/Save the details.
    - Vimeo User Id.
    - Client ID.
    - Client Secret.
    - Access token.


INSTALLATION
------------

Since the module requires external libraries, Composer must be used

Use Composer to download the module, which will download the required libraries:
   composer require "vimeo/vimeo-api"


CONFIGURATION
-------------

 * Configuration Page.
   http://yoursite.com/admin/config/system/vimeo_auth
 * On module configuration page enter the copied/saved details of App from
   https://www.vimeo.com and select the content type from which you have to
   upload the video to Vimeo.


MAINTAINERS
-----------

 * Mahavir singh (mahaveer003) - https://www.drupal.org/u/mahaveer003
 * Neera prajapati (neeraprajapati) - https://www.drupal.org/u/neeraprajapati

Supporting organization:

 * Valuebound - https://www.drupal.org/valuebound
