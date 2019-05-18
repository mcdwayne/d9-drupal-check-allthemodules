INTRODUCTION
------------
The migrate_drupal_d8 module provides a source object that reads content
entities from a Drupal 8 site. These can be used as source data for a D8
migration.

USAGE
-----
The ID for the source plugin is "d8_entity". Use this as the "plugin"
value and then specify the type of entities with "entity_type" and "bundle",
like so:
source:
  plugin: d8_entity
  key: d8_source_site
  entity_type: node
  bundle: article

The 'key' property identifies the source database. See "DATABASE SETTINGS"
below for more details.

The source object will automatically pull all of the fields that belong to your
specified type and bundle.

When using fields that have sub-fields, you must use the delta index, even if
you're using a single-value field.
For instance:
process:
  title: title
  'body/value': 'body/0/value'
  'body/summary': 'body/0/summary'
  'body/format': 'body/0/format'

Title doesn't have sub-fields, so its source is simply "title".
Body, on the other hand, does have subfields, so we need to hit index 0.

DATABASE SETTINGS
-----------------
Add database connection settings in your settings.local.php.

Add a new data source such as:
$databases['d8_source_site']['default'] = array(
    'driver' => 'mysql',
    'database' => 'd8_source_site',
    'username' => 'drupal_db_user',
    'password' => 'drupal_db_password',
    'host' => '127.0.0.1',
    'port' => 3306 );

Then set this as the connection for your database source in your source config
using the key property. The key is the first index, 'd8_source_site' in this case,
and the target is the second index, which is 'default' here.

source:
  plugin: d8_entity
  key: d8_source_site

