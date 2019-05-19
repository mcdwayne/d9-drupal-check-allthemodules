# Vimeo Upload

Javascript based upload to get a video URL from Vimeo without having
to upload it on Drupal first. The result URL can then be used with 
[Video Embed Field](https://www.drupal.org/project/video_embed_field) 
or [Media Entity Vimeo](https://www.drupal.org/project/media_entity_vimeo).

**Work in progress**

## Use case

- When you want to improve AX, so authors do not have to connect to
Vimeo and go back to Drupal.
- When you do not want to store the video file on your server.

## Related project

[Vimeo field Uploader](https://www.drupal.org/project/vimeo_field_uploader)

## Installation

1/ Install and enable this module as usual

`composer require drupal/vimeo_upload`

2/ Download [this repository](https://github.com/websemantics/vimeo-upload)
in the libraries directory. 

You can download it via Drush 

`drush vimeo-upload-plugin` (or alias `drush vudl`) 

So you have /web/libraries/vimeo-upload/vimeo-upload.js

3/ Enable the module 

`drush en vimeo_upload`

## Configuration

### Vimeo

1. Create a [Vimeo](http://vimeo.com/) account
2. Create a [Vimeo app](https://developer.vimeo.com/apps)
3. Request upload access for this application
4. Generate access token for your application on the __Authentication__ tab with
following permissions: Public, Private, Edit, Upload.

### Drupal

1. Go to the Vimeo Upload configuration page
/admin/config/services/vimeo_upload and set your access token.
2. Upload a video via /admin/vimeo_upload/upload

Further integration with Video Embed Field and Media Entity Vimeo is on its way.

## Roadmap

- Configuration form with access token encryption
- Global admin UI for video upload
- Per field form formatter for Video Embed Field
- Per field form formatter for Media Entity Vimeo
