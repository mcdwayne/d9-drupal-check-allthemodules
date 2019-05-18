# Embed Editor Advanced Link

Adds Linkit support for the link field in the entity embed dialog. 

## Requirements

* [entity_embed](https://drupal.org/project/entity_embed) module
* Apply [patch](https://www.drupal.org/files/issues/entity_embed_links-2511404-31.patch)
  to provide a link field in the entity_embed module.
* [linkit](https://drupal.org/project/linkit) module (8.x-4.x only)

## Configuration

* Go to admin menu `Configuration` > `Content authoring` > 
  `Text formats and editors`.
* Select a format and click `Configure` button. E.g. 
  `admin/config/content/formats/manage/rich_text`.
* Go to the Linkit settings in the section of `CKEditor plugin settings`, 
  select a Linkit profile and save.

When you use entity embed dialog, you should see the Linkit field and the 
Linkit attributes elements you have enabled in the last step.

## Maintainers

 - Eric Chen (@eric.chenchao) drupal.org/user/265729