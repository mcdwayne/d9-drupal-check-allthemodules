CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * More Information


INTRODUCTION
------------

Current maintainer: Jim Pease <jmpease@ensemblevideo.com>

The Ensemble Video Chooser module for Drupal enables content editors to easily
embed single videos and/or playlists from their Ensemble Video installation into
their Drupal instance using the CKEditor WYSIWYG editor.

The goal of this module is to simplify the process of embedding Ensemble Video
content that would otherwise require navigation within Drupal and Ensemble Video
to copy and paste desired embed codes.


FEATURES
--------

* CKEditor integration to launch Ensemble Video chooser interface
* Simple interface for searching and choosing videos and playlists
* Easily customize and preview video embed settings
* No need to leave Drupal to embed content
* No need to copy and paste embed codes
* Ensemble Anthem support (requires Ensemble Video 4.3.0+)


REQUIREMENTS
------------

PHP 5.3+
Ensemble Video 4.2.0+
OAuth PECL Extension (https://pecl.php.net/package/oauth)


INSTALLATION
------------

Install the OAuth PECL Extension using the appropriate method for your server
environment.

Next, follow the instructions in the Drupal contributed module installation guide in
order to install the Ensemble Video Chooser module:

https://www.drupal.org/documentation/install/modules-themes/modules-8


CONFIGURATION
-------------

Once installed and enabled, the module requires some additional configuration
before it can be successfully used.  Either click the 'Configure' link in the
'Ensemble Video Chooser' module listing or navigate to
'Configuration' and 'Ensemble Video Chooser' to find the configuration settings.

Launch Url (required)
---------------------
The Launch URL copied from the appropriate LTI configuration in Ensemble Video
-> Administration -> Institution -> LTI Configurations.

Consumer Key (required)
-----------------------
The Consumer Key copied from the appropriate LTI configuration in Ensemble Video
-> Administration -> Institution -> LTI Configurations.

Shared Secret (required)
------------------------
The Shared Secret copied from the appropriate LTI configuration in Ensemble
Video -> Administration -> Institution -> LTI Configurations.

Additional Parameters (optional)
--------------------------------
Any additional parameters to be passed in the LTI tool launch in order to
override default launch and tool UI behavior.  Each value should be on a new
line and be in the format {parameter}={value}, for e.g.

    custom_ensemble_username_param=lis_person_contact_email_primary
    custom_ensemble_default_video_width=640
    custom_ensemble_video_setting_download=true

Available optional parameters for Ensemble Video 4.2.0+:

* custom_ensemble_username_param: Tells the launch handler to use the given LTI
launch parameter (e.g. "lis_person_contact_email_primary"), rather than the
default ("custom_moodle_user_login_id"), when performing username mapping.

* custom_ensemble_username_domain: If the username value being mapped from
Moodle requires a domain-qualification (e.g. "username@example.edu") in order to
match the username within EV, this parameter can be set to provide that domain
(e.g. "example.edu").

The following additional parameters are available for Ensemble Video 4.3.0+:

* custom_ensemble_default_video_width: The default video width embed option is
set to the value of the given encoding.  This parameter can be passed to
override that with a specific static selected width value (must match one of the
available selection options).

* custom_ensemble_video_setting_{setting}: Override default selected video embed
options (e.g. "custom_ensemble_video_setting_download").  Available options and
default values are listed below.
    * showtitle: true
    * autoplay: false
    * showcaptions: false
    * hidecontrols: false
    * socialsharing: false
    * annotations: true
    * captionsearch: true
    * attachments: true
    * links: true
    * metadata: true
    * dateproduced: true
    * embedcode: false
    * download: false

* custom_ensemble_playlist_setting_{setting}:  Override default selected
playlist embed options (e.g. "custom_ensemble_playlist_setting_embedcode").
Available options and default values are listed below.
    * layout: 'playlist'
    * playlistLayout_playlistSortBy: 'videoDate'
    * playlistLayout_playlistSortDirection: 'desc'
    * showcaseLayout_categoryList: true
    * showcaseLayout_categoryOrientation: 'horizontal'
    * embedcode: false
    * statistics: true
    * duration: true
    * attachments: true
    * annotations: true
    * links: true
    * credits: true
    * socialsharing: false
    * autoplay: false
    * showcaptions: false
    * dateproduced: true
    * audiopreviewimage: false
    * captionsearch: true

Once you have the 'Ensemble Video Chooser' module configured, you need to enable
it within the CKEditor.  In order to do so, navigate to 'Configuration' and
'Text formats and editors' under 'Content Authoring'.  Configure the appropriate
format (typically 'Full HTML') to drag the 'Ensemble Video' button from the
'Available buttons' to the 'Active toolbar' in the 'Toolbar Configuration'
section.  Format configuration depends largely on the needs of the installation
and falls out of the scope of this document.  Note, however, that the format
must allow addition of '<iframe>' elements.


MORE INFORMATION
----------------

For more information regarding Ensemble Video, visit our website at
https://www.ensemblevideo.com/

For more information regarding this module, or to submit a feature request or
report a bug, go to
http://drupal.org/project/ensemble_video_chooser
