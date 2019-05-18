Pause the mediaplayer
Module for Drupal 8.x

Official website: https://pause-player.com/
Drupal project page: http://drupal.org/project/pause_player
Reports bugs and suggestions here: http://drupal.org/project/issues/pause_player

SUMMARY
---------------------------------------------------
The Pause Player module adds formatter options which allows articles authors to use the Pause Player on videos fields.
With a file field, you can add a local video file. With a link field, you can define an URL of a local video file.
If you choose Pause Player for display this fields, you have an HTML5 video player for play videos !

The module has many options to configure the video player :
* Define width, height, ratio of the video
* Add CSS classes on the HTML container element of the video player
* Autoplay, loop, volume, mute
* Display a start button
* And more if you have the commercial version of the Pause mediaplayer :
** Sets a startup content that will be displayed before playing video
** Configure the disappear mode of the video player

REQUIREMENTS
---------------------------------------------------
* This module depends on the File module, which is part of Drupal core.

INSTALLATION OF PAUSE PLAYER INSIDE THE MODULE
---------------------------------------------------
The module contains a free version of Pause Player the videoplayer.

* For install the last free version, go to the official site and download the ZIP archive : https://pause-player.com/free-version/
* The installation of the commercial version is similar to the installation of the free version :
* Extract the zip archive and put the contents of the extracted folder in /modules/pause_player/player/

* Go to Administration > Reports > Status reports (admin/reports/status) to
  check your configuration.

LICENSE
--------
* This Drupal plugin is free and covered by the same license of Drupal core : GNU General Public License.
* The video player "Pause" is proprietary software whose copyright holder is the company "Aizier Médias".

VERSIONS
---------
# Version 1.3
	Release : 2018-01-05
	Evolutions :
		- New player fully HTML5! Flash technology is discontinued in this new major release of the video player: lighter, faster, improved touchscreen compatibility. 

# Version 1.2
	Release : 2017-09-24
	Corrections :
		- The method getUsername() was used in the file PausePlayerFormatter.php, it is deprecated in Drupal 8 and will be removed before Drupal 9 : replaced by getDisplayName()
		- Spelling mistake in README.txt

# Version 1.1
	Release : 2017-09-18
	Corrections :
		- Removing of @file tag in the file PausePlayerFormatter.php for respect Drupal coding standards
		- Replace t() function with $this->t() in PHP classes

# Version 1.0
	Release : 2017-09-08
	First stable version
