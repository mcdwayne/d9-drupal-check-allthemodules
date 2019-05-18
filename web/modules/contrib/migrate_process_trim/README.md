# Migrate Process Trim

Sometimes you need to trim the results of a process pipeline. Often this is to remove leading and/or trailing spaces, but it could by any character. PHP provides this as the trim(), ltrim() and rtrim() functions. This module allows you to use those easily in a migration:

```yaml
  # Trim spaces from before and after:
  field_body:
    -
      plugin: trim
      source: body
  # Trim a leading full colon:
  field_name:
    -
      plugin: ltrim
      mask: ':'
      source: name
  # Trim trailing commas and spaces:
  field_city_stage:
    -
      plugin: rtrim
      mask: ', '
      source: city_state
```

