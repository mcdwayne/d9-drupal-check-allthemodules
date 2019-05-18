Digital size formatter
----------------------

This is a tiny module that provides a digital size formatter to core numeric
field types: integer, float and decimal. The formatter assumes that the field
value is stored as bytes. If the stored value is float or decimal, the formatter
will convert that value to the closest integer. The formatter uses the standard
Drupal format_size() function.
