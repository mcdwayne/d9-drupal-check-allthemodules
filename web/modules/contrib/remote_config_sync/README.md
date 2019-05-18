# Remote Config Sync

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

## INTRODUCTION

Remote Config Sync module allows you to push the configuration files from 
development to production site with a click of a button. By using this module 
you don't have to waste your time with the manual export/import process. 

You can choose between just pushing the configuration and pushing and importing 
it in a single step. If you choose to just push the config, then you need to go
to the config Synchronize page ('admin/config/development/configuration') and 
review and import changes. Before using this module, please make sure that your
'sites/default/files/config_HASH/sync' directory is properly configured.

## REQUIREMENTS

None.

## INSTALLATION

1. Install module as usual via Drupal UI, Drush or Composer.
2. Go to "Extend" and enable the Remote Config Sync module.
3. Go to the Configuration -> Development -> Remote config sync and start using
the module.

## CONFIGURATION

1. Create your DEVELOPMENT site.
2. Copy your DEVELOPMENT site to your PRODUCTION server. This will ensure that
the sites UUIDs are the same.
3. Install the Remote Config Sync module on both sites.
4. On your DEVELOPMENT site go to the Remotes page:
'admin/config/development/remote-config-sync/remotes' and enter the URL of your
PRODUCTION site and copy the security token from your PRODUCTION Settings page
that can be found here: 'admin/config/development/remote-config-sync/settings'.
5. You can start pushing the configuration from DEVELOPMENT site to PRODUCTION.

### AUTHOR

Goran Nikolovski  
Website: http://gorannikolovski.com  
Drupal: https://www.drupal.org/u/gnikolovski  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: http://www.studiopresent.com  
Drupal: https://www.drupal.org/studio-present  
Email: info@studiopresent.com  
