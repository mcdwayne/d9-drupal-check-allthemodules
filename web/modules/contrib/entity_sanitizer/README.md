# Entity Sanitizer
The Entity Sanitizer module provides the Drush `entity-sanitize` command. It 
creates (SQL) Database queries to replace all values for all fields with a 
standardized message. This allows you to safely reuse content structures from 
production databases without exposing production user content.

## Supported field types
The following field types currently have a sanitize plugin within the module.
<ul>
<li>text_with_summary</li>
<li>string</li>
<li>string_long</li>
<li>text_long</li>
<li>email</li>
<li>link</li>
<li>image</li>
<li>file</li>
<li>telephone</li>
<li>address</li>
<li>geolocation</li>
</ul>

The following fields are currently considered to be safe and are not altered. The reason is that they're either an atomic value defined in code/configuration or a reference to another (sanitized) entity.
<ul>
<li>block_field</li>
<li>boolean</li>
<li>dropdown</li>
<li>datetime</li>
<li>comment</li>
<li>list_string</li>
<li>list_integer</li>
<li>entity_access_field</li>
<li>entity_reference</li>
<li>entity_reference_revisions</li>
<li>dynamic_entity_reference</li>
<li>video_embed_field</li>
<li>weight</li>
</ul>

## Adding a new field type
You can add support for a field type by adding a FieldSanitizer plugin. Take a 
look at the FieldSanitizer plugins in this module for examples.
