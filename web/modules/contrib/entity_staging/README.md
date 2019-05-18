#Entity Staging

##Exporting content:


1. After enabling this module, go to /admin/config/system/content-staging:
     - Choose all entity types / bundles you want to export the content
     - Change the default content staging directory ('../staging' by default).
       This directory is relative to the drupal root.

2. Run the drush command: `$ drush export-content (ex)`

3. Don't forget to export the entity_staging configurarion (entity_staging.settings.yml)

##Importing content:

1. Run the drush command to update migration entities
   regarding the previous configuration: `$ drush update-migration-config`

2. Run the migration: `$ drush mi --group=entity_staging (umc)`
