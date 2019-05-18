Overview
--------

Provides a baseline set of configuration for using core Media module for
embedding media/images in WYSIWYG fields. Designed to be enabled so you get the
goodness of default configuration, and then removed as you don't need it hanging
around - the configuration will stay with your site.

Features
--------

*   [all the goodness from PNX Media](https://drupal.org/project/pnx_media#features) _plus_
*   An entity browser designed to work with [entity embed](https://drupal.org/project/entity_embed) and [embed](https://drupal.org/project/embed) modules
*   An embed view mode for media entities
*   An entity embed button

Requirements
------------

All dependencies are detailed in the info file but in summary

*   [Embed](https://drupal.org/project/embed)
*   [Entity embed](https://drupal.org/project/entity_embed)
*   [PNX Media](https://drupal.org/project/pnx_media) which has dependencies on
    *   Core media (Drupal 8.4+)
    *   [Media entity browser 2.x](https://drupal.org/project/media_entity_browser) (for a nice view and styling in the entity browser)
    *   [Inline Entity Form](https://drupal.org/project/inline_entity_form) for inline creation
    *   [Video Embed field 2.x](https://drupal.org/project/video_embed_field) for video support

Configuration
-------------

Visit /admin/config/content/formats and add the _Embed media_ button to the
required text formats. Make sure your allowed html tags includes and that you
enable the _Display embedded entities_ filter. You may also want to review the
display settings for the embed view mode for media and image.

Known problems
--------------

Please use the issue queue to report issues.
