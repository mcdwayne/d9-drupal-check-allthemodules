Config Pages Import
======

Imports config entities from schemas to config pages.

###Import from the single config entity:
```
$import = \Drupal::service('config_pages_import');
$import->import('config_enity_name');
```

###Import from the module:
```
$import = \Drupal::service('config_pages_import');
$import->importFromModule('module_name');
```
**Notice**: Module Info file must have a list of needed config entities:
```
config_pages:
  - config_enity_name_1
  - config_enity_name_2
```

Look at `/config_pages_import/config/schema/config_pages_import_test.schema.yml` to overview the opportunities. 

To run tests execute from the core directory:

`sudo -H -u apache_user bash -c '../../vendor/bin/phpunit ../modules/custom/config_pages_import/'`