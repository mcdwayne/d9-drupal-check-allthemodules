-- SUMMARY --

Audio Embed Field creates a simple field type that allows you to embed audio.
From Soundcloud and Custom URLs to a field, and also integrates with.
The media_entity module for Drupal 8.
Simply provide the URL to the audio and the module.
Creates an embedded audio player.


-- REQUIREMENTS --

For the audio_embed_media module, you will need the media_entity module.
The base module also requires field and image from core.

The module can make use of colorbox, but it is not required.


-- INSTALLATION --

* Install as usual as per http://drupal.org/node/895232 for further information.

* You can add Audio embed fields via the normal interface on content types.

* If using media_entity, the audio_embed_media module included provides
  a media_entity bundle bridge.

* When you add fields, select the providers you want to use,including Custom URL
  and Soundcloud. If using Soundcloud, you need to obtain a client ID
  from the Soundcloud Developers site: https://developers.soundcloud.com/


-- CUSTOMIZATION --

* You may override the template files in the /templates folder in your theme.
  See Twig debugging output for possible override suggestions.


-- CONTACT --

Current maintainers:
* David Lohmeyer (vilepickle) - https://www.drupal.org/u/vilepickle
