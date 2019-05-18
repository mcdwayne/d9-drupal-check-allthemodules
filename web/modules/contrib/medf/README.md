# Media Entity Download Filter

A Linkit CKEditor filter to link to Media Entities in the ckeditor. This module
searches for the `field_file` on a media entity, then displays the download URI 
of that media entity instead of linking to the media detail page.


## How to enable?

Go to "Configuration > Content authoring > Text formats and editors", select
a text format, with which you want to use this filter, and check
"Link media direct to download url". 

## Note

This feature is based on the `media_entity` contrib module. It is not compatible
with the media module in core since Drupal 8.4. 
