CKEditor IndentBlock
====================

Description
===========
The CKEditor IndentBlock plugin adds the functionality of indenting text paragraphs using CKEditor. The plugin doesn't
come with its own buttons, but uses the same buttons as the built-in CKEditor Indent plugin. The configuration allows
to enable/disable the CKEditor IndentBlock plugin individually for each text format.

Installation
============
1. Download the plugin from http://ckeditor.com/addon/indentblock
2. Place the plugin in the root libraries folder (/libraries).
3. Enable the CKEditor IndentBlock module in the Drupal admin menu 'Extend >> List'.

Adding paragraph indentation to a text format
=============================================
1. Go to admin menu 'Configuration >> Text formats and editors'.
2. Click on the Configuration button of the text format (i.e. Simple HTML).
3. If not already in the toolbar, drag the buttons with the title tags indent and outdent into it, which enables the
   built-in CKEditor Indent plugin for lists.
4. Open the vertical tab 'Indent Block' and make sure the plugin is enabled.
5. Make sure, the tag <p class> is added to the field 'Allowed HTM tags', otherwise the 'Indent' and 'Outdent' buttons
   will not become active for paragraphs despite the IndentBlock plugin being enabled.

Dependencies
============
This module requires the core CKEditor module and the contributed Libraries module.

Uninstallation
==============
1. Uninstall the module from the admin menu 'Extend >> Uninstall'.


MAINTAINERS
============
Christian Meilinger - https://www.drupal.org/u/meichr
