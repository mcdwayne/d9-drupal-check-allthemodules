# Media Entity Dream Broker
This module provides Dream Broker integration for Media entity (i.e. media source 
plugin for Dream Broker resources).

### Usage
With this module you are able to embed Dream Broker online videos without any 
additional APIs. Module uses automatically generated iframe code provided by 
Dream Broker Studio to embed HTML5/Flash videos.

After enabling the module, you can create a new Media Type choosing "Dream Broker"
on the media source dropdown.

A source field will be automatically created and configured on the Media Type if
this is the first Dream Broker type on the site. If you need to have additional
types, you can choose to reuse an existing field as source, or create one field
per type. Source fields for the Dream Broker Media Type need to be plain text or
link fields.

To render Dream Broker online video, please select Dream Broker embed formatter 
to use with Dream Broker Url field in display settings.

Please refer to the Media documentation for more instructions on how to work
with Media Types.

Project page: http://drupal.org/project/media_entity_dreambroker

Maintainers:
 - @mitrpaka (drupal.org/u/mitrpaka)
 - @kirkkala (drupal.org/u/kirkkala)
