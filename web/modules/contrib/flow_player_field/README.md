CONTENTS OF THIS FILE
---------------------
  
  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Maintainers


INTRODUCTION
------------

This is a Drupal module enabling the users of Flowplayer
(https://flowplayer.com/) to include embed videos to articles.

Provides a field type for displaying videos from Flowplayer

 * For a full description of the module visit:
   (https://www.drupal.org/project/flow_player_field

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/flow_player_field


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

  * drupal:field
  * drupal:image

  * [Composer](https://getcomposer.org/) 
  * [Drush](https://www.drush.org/)
  * [Guzzle](https://github.com/guzzle/guzzle)

INSTALLATION
------------

  * Install the Flowplayer module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.

  * In order to install the module we will need composer installed.
  
  * After composer is installed download the module folder and add it in 
    ```[root]/web/modules/flow_player_field```
  
  * Run ```composer install``` for the dependencies to be installed
  * After everything is installed we can proceed with the configuration


CONFIGURATION
-------------

  1. Navigate to Administration > Extend and enable the Flowplayer
     module.

  2. There is a group called FLOWPLAYER and we should check 
     Flowplayer Field and Flowplayer WYSIWYG.
  
  3. After we check those checkboxes we should scroll down and 
     click the *install* button.
  
  4. After the module is installed we should go to 
     admin/config/flow_player_field and fill out the fields on the form.
    * Api Key
    * Site ID
    * Search results number (has default value)
    * Embed code (has default value)
    
  5. Now we save the configuration
  
  6. The last step is configuring our CKEditor. We will do that by going to 
     /admin/config/content/formats and configure the editor that we want to use. 
     When we click the configure button we have to:
  
    1. Drag the flowplayer icon to the toolbar.
    
    2. Check the Flowplayer WYSIWYG checkbox in the Enabled filters group.
  
    3. Click Save configuration.


MAINTAINERS
-----------

 * Flowplayer Enterprise (flowplayer) - https://www.drupal.org/u/flowplayer
