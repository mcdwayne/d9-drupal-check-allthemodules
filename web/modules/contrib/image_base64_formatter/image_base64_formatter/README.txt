This module adds an FieldFormatter for an Image field,
which let's you base64 of an image directly

While most of the code comes from the core module image's
ImageFormatter.php, some rewriting with templating has
been done. Should be stable.

Usage:
(1)
After installation of this module you'll get a new
format type "Image Base64" you can assign to any Image Field
in a Content Type. Just go to the Type's "Manage Display"
and choose "Image Base64" instead of "Image" from the Format
Combo-Box.

(2)
Same goes for Image Content Fields in Views, choose
"Image Base64" as format.
