CONTENTS OF THIS FILE
---------------------
 
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 
 
INTRODUCTION
------------
 
 Easy as hell module for play video background.
 ## Notes

 * All modern desktop browsers are supported.
 * IE9+
 * iOS plays video from a browser only in the native player.
  So video for iOS is disabled, only fullscreen poster will be used
 * Some android devices play video, some not — go figure.
   So video for android is disabled, only fullscreen poster will be used.
 

REQUIREMENTS
------------
 
  This module relies on the jQuery vide plugin.
 
 
INSTALLATION
------------
 
 * Place the videobackground module into modules directory.
 * Enable this module by navigating to: Administration > Extend
 * Go to module configuration page under the visibility settings add CSS
   selectors for allowing video to play in background.
 * Prepare your video in several formats like '.webm', '.mp4' for cross browser
   compatibility, also add a poster with .jpg, .png or .gif extension:

		path/
		├── to/
		│ ├── video.mp4
		│ ├── video.ogv
		│ ├── video.webm
		│ └── video.jpg

 	 Video and poster must have the same name.
 * Setup video background options, if you need it. By default video is muted,
   looped and starts automatically.
 
 

CONFIGURATION
-------------
 
 * After all settings you may need to do some custom style to make background of
 	 page without background color.
	* For normal visitor to view background video you must have to give permission
  "Use video background".
	* Enjoy it.
 
 
MAINTAINERS
-----------
 
8.x-5.x Developed and maintained by:
 * Lav Rai (https://www.drupal.org/user/2341120)
