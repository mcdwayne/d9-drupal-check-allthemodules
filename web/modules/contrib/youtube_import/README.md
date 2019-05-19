CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
The YouTube import module imports data using the most recent version of the
YouTube API into a content type as selected and configured by the administrator.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/sandbox/shaunfrisbee/2575089

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/2575089

REQUIREMENTS
------------
This module require the following modules
 * Entity API (https://drupal.org/project/entity)

Curl must be installed and php configured to use it. Check for a "Curl" entry
in phpinfo() or at the linux command line type:
 * $ php -i | grep "curl"

To enable curl, installed :
 * sudo apt-get install php5-curl
After installing libcurl you should restart the web server with one of the
 following commands,
 * sudo /etc/init.d/apache2 restart OR sudo service apache2 restart


RECOMMENDED MODULES
-------------------
 * Transliteration (https://drupal.org/project/transliteration):
   When enabled, the thumbnail images downloaded from YouTube will have file 
   names that are predetermined as safe. Otherwise, the module will use the
   YouTube video ID and hope for the best.
 
 * YouTube Field (https://www.drupal.org/project/youtube):
   When enabled, the field allows the video URL to be saved to the content
   type and rendered as a video.

 * Markdown filter (https://www.drupal.org/project/markdown):
   When enabled, display of the project's README.md help will be rendered with
   markdown.

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further documentation

CONFIGURATION
-------------
 * Configure user permissions in Administration >> People >> Permissions:

   - YouTube Import

     Allows the user access to configured and run the import manually

 * Configure the module to use the API in
   Administration >> Content >> YouTube Import

 * To obtain an API key, follow the instructions at
   https://developers.google.com/youtube/registering_an_application?hl=en

 * You will need to complete the section "Create your project and select
   API services"

 * You can skip the section "Creating OAuth 2.0 credentials". You will
   need the to use the "Server keys" subsection of "Creating API keys"
   in order to get the key you need.

MAINTAINERS
-----------
Current maintainers:
 * Shaun Thomas Frisbee (shaunfrisbee) - https://drupal.org/user/2750545
