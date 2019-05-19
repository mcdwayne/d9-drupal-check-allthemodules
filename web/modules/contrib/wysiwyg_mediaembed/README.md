# wysiwyg_mediaembed
CKEditor MediaEmbed Plugin For Drupal 8

This plugin module adds the [Media Embed button](https://ckeditor.com/addon/mediaembed) button to CKEditor in Drupal 8.

* Module project page: https://drupal.org/project/wysiwyg_mediaembed
* http://ckeditor.com/addon/link

Installation
============

1. Download the plugin from https://ckeditor.com/addon/mediaembed at least
version 0.7.
2. Place the plugin in the root libraries folder (/libraries).
   The path would be libraries/mediaembed/plugin.js
3. Enable Media Embed Button (wysiwyg_mediaembed) module in the Drupal admin.
4. Configure your CKEditor toolbar to include the button.

Follow these steps to make sure the plugin works for Basic HTML text format:

1. Drag the button from the toolbar to the active items
2. Make sure you add any tag that you want to allow in the editor:
   <iframe width height etc><div>

Troubleshooting
===============

The site status report page will indicate if the library cannot be found.

Issue queue: https://www.drupal.org/project/issues/wysiwyg_mediaembed


Background
==========

This module was largely inspired by the discussion at 
http://groups.drupal.org/node/55688#comment-634648

Module updated in 2014 by HongPong: https://www.drupal.org/user/60005/
Credit for bug patches to tregismoreira, acrosman

Rewritten for Drupal 8 by gnuget: https://www.drupal.org/u/gnuget