The Field Wistia module provides a simple field that allows you to add a
Wistia video to a content type, user, or any entity.

Display types include:

 * Wistia videos of various sizes.
 * Wistia thumbnails with image styles.

This module is a lightweight alternative to Media Module. If
you're looking for a way to add video fields from more than one provider, you
may want to consider looking into Media, but if you're
using Wistia, you already know they're the best!


Installation
------------
Follow the standard contributed module installation process:
http://drupal.org/documentation/install/modules-themes/modules-8


Requirements
------------
All dependencies of this module are enabled by default in Drupal 8.x.


Use
---
To use this module, create a new field of type 'Wistia video'. This field will
accept Wistia URLs of the following formats:

 * wisitia.com/medias/[video_id]

It will not be a problem if users submit values with http:// or https:// and
additional parameters after the URL will be ignored.


Configuration
-------------
In both Views and these field settings, a Wistia field can be output as a
video of either one of three sizes or a custom size, with the ability to
autoplay. The thumbnail of the Wistia image can also be used and
can link to either the content, or nothing at all.

To configure the field settings:

 1. click 'manage display' on the listing of Content Types (under Structure)
 2. click the configuration gear to the right of the Wistia field


Support
-------
Please use the issue queue for filing bugs with this module at
https://www.drupal.org/project/field_wistia
