The Migrate Process Inline Images module provides a migrate process plugin that
fixes up img tags (e.g. within a node body) to contain the file entity metadata
needed to allow the image properties to be edited in the WYSIWYG editor.
Additionally, with the metadata, the `Content > Files` page will show the
correct cross-reference to where each entity file is used.

Without the file entity metadata, the image editor pane will not correctly
identify the image referenced in the img tag, and, consequentially, it will
not be possible to select a new left or right alignment for the image.

Example Use in Migration Configuration
======================================
Use the inline images process plugin in the process section of any migration
configuration, such as the one shown below.

process:
  body/0/value:
    -
      plugin: inline_images
      base: files/image
      source: body

The `base` configuration stipulates where inline images are found on the
Drupal site. For example, if `public://` is `files`, and the `basic_html`
format is configured to upload images to a directory named `image`, then the
base directory would be `files/image`. The default is `sites/default/files`.
