# Migrate Process URL

A custom migration may only have a URL to import into a field. If using Drupal core's link field, you have to assign the value directly to the `uri` column:

```yaml
  field_my_website/uri:
    source: my_custom_url_source
```

The problem is, sometimes the source field will not be in the correct URL format for Drupal core. 

## Generating link fields

This module provides a new process plugin, <strong>field_link_generate</strong> the creates an array for use with the `field_link` process plugin:

```yaml
  field_my_website:
    -
      plugin: field_link_generate
      source: my_custom_url_source
      title_source: my_custom_title_source
    -
      plugin: field_link
      uri_scheme: 'http://'
```

## Validating URLs

This module also provides a way to skip a row or process if the url isn't valid:

```yaml
   field_my_website:
    -
      plugin: skip_on_invalid_url
      source: my_custom_url_source
      method: process
    -
      plugin: field_link_generate
      title_source: my_custom_title_source
    -
      plugin: field_link
      uri_scheme: 'http://'
```

If you only want to validate absolute URLs -- ones starting with scheme such as `http` -- use the `absolute` key:

```yaml
   field_my_website:
    -
      plugin: skip_on_invalid_url
      source: my_custom_url_source
      method: process
      absolute: true
    -
      plugin: field_link_generate
      title_source: my_custom_title_source
    -
      plugin: field_link
      uri_scheme: 'http://'
```