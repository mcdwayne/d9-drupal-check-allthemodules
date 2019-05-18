
CONTENTS OF THIS FILE
---------------------

 * About getID3()
 * About getID3() Drupal Module
 * Installation

ABOUT getID3()
--------------

getID3() is a PHP script that extracts useful information
(such as ID3 tags, bitrate, playtime, etc.) from MP3s & other multimedia file
formats (Ogg, WMA, WMV, ASF, WAV, AVI, AAC, VQF, FLAC, MusePack, Real,
QuickTime, Monkey's Audio, MIDI and more).

ABOUT getID3() Drupal Module
----------------------------

The getID3() Drupal module facilitates the installation and management of the
getID3() PHP library, used to extract useful information from MP3s and other
multimedia file formats.

This API module is used by other modules to ensure that getID3() is correctly
installed. Developers who need the getID3() functionality can make this a
dependency by their module, and then call the getid3_load() function to
properly use the getID3() library without having to worry about whether
it's installed or not.

INSTALLATION
------------

Please read the INSTALL.txt file present in the module folder and follow the
instructions to install the module.
