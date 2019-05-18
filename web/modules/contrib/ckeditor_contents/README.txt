CKEditor Table of Contents
==============================

Description
===========
This module enables the Table of Contents plugin from CKEditor.com in your WYSIWYG.
This plugin adds a simple Table of Contents widget which crawls the content for
Heading-Tags (<h1>, ... ,<h6>) and adds Anchor tags to headings.

Usage
=====
Go to the Text formats and editors settings (/admin/config/content/formats) and
add the Table of Contents Button to any CKEditor-enabled text format you want.

Installation
============
1. Download the plugin from http://ckeditor.com/addon/contents
2. Place the plugin in the root libraries folder (/libraries).
3. Enable CKEditor Accessibility Checker module in the Drupal admin.

With composer add the following to your repositories then `composer require ckeditor/contents`
```
"ckeditor.contents": {
  "type": "package",
  "package": {
    "name": "ckeditor/contents",
    "version": "0.11",
    "type": "drupal-library",
    "extra": {
      "installer-name": "contents"
    },
    "dist": {
      "url": "https://download.ckeditor.com/contents/releases/contents_0.11.zip",
      "type": "zip"
    }
  }
},
```

Dependencies
============
This module requires the core CKEditor module .

Uninstallation
==============
1. Uninstall the module from 'Administer >> Modules'.