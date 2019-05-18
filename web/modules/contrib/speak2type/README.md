CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Ckeditor Speak2type module is a simple plugin for CKEditor 4, adding the
ability to type into the editor using speech.

It's compatible with latest Chrome and Safari versions. It should also work in
Firefox after activating the proper experimental flag.

 * For a full description of the module visit:
   https://www.drupal.org/project/speak2type

 * For a demo of the functionality visit:
   https://comandeer.github.io/ckeditor4-plugin-speak2type/

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/speak2type


REQUIREMENTS
------------

This module requires the speak2type library available on Github.

 * CKEditor4-plugin-speak2type -
   https://github.com/Comandeer/ckeditor4-plugin-speak2type/tree/v0.0.2


INSTALLATION
------------

Install the Ckeditor Speak2type module as you would normally install
a contributed Drupal module.
Visit https://www.drupal.org/node/1897420 for further information.

Download the plugin from [GitHub]
(https://github.com/Comandeer/ckeditor4-plugin-speak2type/tree/v0.0.2). Place
the plugin in the root libraries folder (/libraries/speak2type).
Please see GITHUB documentation for more details.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Content Authoring > Text
       formats and editors to configure the desired profile.
    3. Move the microphone icon enter the active toolbar and save.
    4. After clearing the browser's cache, the new button will appear in the
       WYSIWYG toolbar.
    5. Select the microphone button and speak into the microphone. The words
       should appear as the content in the editor.


Please note: If you are accessing your webpage through HTTP you'll need to
explicitly give Google Chrome access to your microphone every time you start
recognition - this is done through a pop up dialogue which quickly becomes
annoying. This can be resolved by providing your webpage through HTTPS.


MAINTAINERS
-----------

 * abu zakham (abu-zakham) - https://www.drupal.org/u/abu-zakham

Supporting organization:
 * Vardot - https://www.drupal.org/vardot
