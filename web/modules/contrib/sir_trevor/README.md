CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Required modules
 * Recommended modules
 * Required libraries
 * Installation
 * Configuration
 * Adding new "blocks"

Introduction
------------
The Sir Trevor module integrates the sir-trevor library in Drupal (http://madebymany.github.io/sir-trevor-js) by
providing a new field.

Required modules
----------------
This module requires no Drupal modules


Recommended modules
-------------------
None at the moment.

Required libraries
------------------
This module requires the sir-trevor (https://github.com/madebymany/sir-trevor-js/releases) library to be installed in
 `/libraries/sir-trevor`.

Installation
------------
 * Download the sir-trevor library from https://github.com/madebymany/sir-trevor-js/releases and extract it.
 * Copy the extracted files to `\libraries\sir-trevor` in your document root.
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8
   for further information.

Configuration
-------------

 * Add a new `Sir Trevor field` to your entity of choice

 * Optionally customize the field's form options in the `Manage form display` tab.

 * Optionally customize the field's display options in the `Manage display` tab.

Adding new "blocks"
-------------------
Modules can define their own blocks by adding a example.sir_trevor.yml file to their module root. This file can contain several block definitions using the following format:

```yaml
block_machine_name:
  assets:
    display:
      js: path/to/display.js
      css: path/to/display.js
      dependencies:
        - some/library
    editor:
      js: path/to/editor.js
      css: path/to/editor.js
      dependencies:
        - some/library
    icon_file: path/to/icons.svg
  template: blocks/two_columns/two_columns
```
Only the _template_ key is required, but most blocks will at least define _assets.editor.js_ and _assets.icon_file_ 

Based on the definitions provided in this file, the custom block will be registered with the module, adding it's assets as libraries (i.e. _sir_trevor/block.block_machine_name.editor_ or _sir_trevor/block.block_machine_name.display_) when applicable.

Adding new "mixins"
-------------------
Modules can define their own mixins by adding a example.sir_trevor.yml file to their module root. This file can contain several block definitions using the following format:

```yaml
mixin_machine_name:
  mixin: true
  assets:
    editor:
      js: path/to/editor.js
      css: path/to/editor.js
      dependencies:
        - some/library
    icon_file: path/to/icons.svg
```

The _mixin_ key is required for the mixin to be correctly registered. 

Based on the definitions provided in this file, the custom mixin will be registered with the module, adding it's assets as libraries (i.e. _sir_trevor/mixin.mixin_machine_name.editor_) when applicable.
