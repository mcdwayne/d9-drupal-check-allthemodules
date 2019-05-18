## About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

## About Media entity Pinterest

This module provides Pinterest integration for Media entity (i.e. media type
provider plugin).

### Without Pinterest API
If you need just to embedded Pinterest pins, boards, or profiles you can use 
this module without using the Pinterest API. That will give you access to the 
fields available from the url/embed code: pin, board, and user ids.

You will need to:

- Create a Media bundle with the type provider "Pinterest".
- On that bundle create a field for the Pinterest url/source 
  (this should be a plaintext or link field).
- Return to the bundle configuration and set "Field with source information" to
  use that field.

[Pinterest Developers site](https://developers.pinterest.com)

### With Pinterest API

Integration with the Pinterest API is not currently supported, but will likely 
be added in the near future.

[API Docs](https://developers.pinterest.com/docs/getting-started/introduction)

Project page: http://drupal.org/project/media_entity_pinterest

Maintainers:
 - Grant Gaudet drupal.org/user/360002
