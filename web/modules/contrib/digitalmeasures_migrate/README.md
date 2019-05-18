# Digitalmeasures Migrate

The digitalmeasures_migrate module provides a Digital Measures interface for Drupal 8 migrations. The module provides a source plugin to query the API, so that objects within your schema can be stored as Drupal entities.

## Installation and config

This module relies on [Migrate Plus](https://www.drupal.org/project/migrate_plus). So we'll need to install that and this module using Composer:

```shell
composer require drupal/migrate_plus drupal/digitalmeasures_migrate
```

And enable the module either via the web UI or via Drush:

```shell
drush en -y migrate_plus digitalmeasures_migrate
```

Once enabled, you need to configure your Digital Measures credentials in the module.

1. Login to your site with administrator privileges.
2. Navigate to **Admin &gt; Config &gt; System &gt; Digital Measures**.
3. Select your **API Endpoint**.
4. Enter your Digital Measures provided **Username**.
5. Enter your **Password**.
6. Click **Submit**.

If your site is under version control -- and it should! -- export your site configuration using the web UI or Drush.

## Using the module

Other than configuring the endpoint, this module does not provide any UI. Instead, you need to write custom migrations that leverage the `digitalmeasures_api` source plugin.

Digital Measures (DM) is a REST based API with the following URL format:

```
<apiUrl>/v4/<resource>/<schema_key>/[index_key][:[entry_key]]
```

If successful, the result of the query is provided as an XML document.

You can use this plugin to specify the object to fetch. Note the `index_key` and `entity_key` are optional:

```yaml
source:
  plugin: digitalmeasures_api
  resource: User
  schema_key: MY_SCHEMA_KEY
  index_key: COLLEGE
  entry_key: Example+College+of+dubious+things
```

Credentials are provided by the module configuration. If you need to force a particular migration to use the testing API endpoint, you can use the `beta` key:

```yaml
source:
  plugin: digitalmeasures_api
  resource: User
  schema_key: MY_SCHEMA_KEY
  beta: 'yes'
  index_key: COLLEGE
  entry_key: Example+College+of+dubious+things
  item_selector: /Users/User
```

The contents of schemas depend on how you have DM configured, so this plugin also requires you to specify how to uniquely identify each "row" in the resulting XML document using an XPath. This Xpath can be specified using the `item_selector` key:

```yaml
source:
  plugin: digitalmeasures_api
  resource: User
  schema_key: MY_SCHEMA_KEY
  beta: 'yes'
  index_key: COLLEGE
  entry_key: Example+College+of+dubious+things
  item_selector: /Users/User
```

Furthermore, you need to specify what fields from each "row" in the XML document to retrieve and make available to the migration:

```yaml
fields:
 -
  name: username         # The field name in the migration.
  label: Username        # Used for display in: admin > structure > migrate
  selector: '@entryKey'  # XPath within the "row" to the row's unique ID.
```

And the data type of the ID field:

```yaml
ids:
  username:
    type: string
```

All together, your source section will look like this:

```yaml
source:
  plugin: digitalmeasures_api
  resource: User
  schema_key: MY_SCHEMA_KEY
  beta: 'yes'
  index_key: COLLEGE
  entry_key: Example+College+of+dubious+things
  item_selector: /Users/User
  fields:
   -
    name: username
    label: Username
    selector: '@entryKey'
  ids:
    username:
      type: string
```

## Dealing with timeouts

The above describes a "direct" migration; you query the API, and the result is a Drupal entity. Direct migrations work when the number of items and the data in your DM schema is small. 

If you are experiencing timeouts, it could be that your `http_client_config.timeout` is too low. Digital Measures relies on Guzzle to query the API. You can increase the timeout by adding the following to your `settings.php`:

```php
/**
 * Increase the HTTP timeout to handle really big schemas.
 */
$settings['http_client_config']['timeout'] = 120;
```

The above sets the timeout to 120 seconds.

This plugin should be used to create profile entities within the Drupal site.

## Indirect migrations

If you have a large set of data in your schema, you may want to create "indirect" migrations. These migrations allow you to minimize the total time you are querying the DM API, allowing for more total data to be downloaded without failures.

Staged migrations, as the name implies, stage data from the API to a custom database table provided by this module:

```
          +------------------------------+
          |                              |
          |   Digital Measures API       |
          |                              |
          +--------------+---------------+
                         |
Remote                   |
+----------------------------------------------------+
Local                    |
                         |
                   Stage migration
                         |
                         v
          +--------------+---------------+
          |                              |
          |   Stage table                |
          |                              |
          +--------------+---------------+
                         |
                         |
                   Entity migration
                         |
                         v
          +--------------+---------------+
          |                              |
          |   Drupal entity              |
          |                              |
          +------------------------------+

```

Two destination plugins are provided for this purpose:

* `digitalmeasures_api_user_staging` which stores DM usernames and IDs in the `digitalmeasures_migrate_usernames` table.
* `digitalmeasures_api_profile_staging` which stores specified portions of a DM profile to the `digitalmeasures_migrate_profile` table.

The succeeding "entity migration" relies on a complimentary pair of source plugins which pull data from the matching source table:

* `digitalmeasures_api_user_staging`
* `digitalmeasures_api_profile_fragment`

### Fan in

Sometimes you need to filter which items you import from Digital measures. In that case, you can create multiple initial migrations using different `index_key`s and `entry_key`s as necessary:

```
          +------------------------------+
          |                              |
          |   Digital Measures API       |
          |                              |
          +--+-----------+-----------+---+
             |           |           |
Remote       |           |           |
+----------------------------------------------------+
Local        |           |           |
             v           |           v
      Migration1    Migration2    Migration3
             |           |           |
             v           v           v
          +--+-----------+-----------+---+
          |                              |
          |   Stage table                |
          |                              |
          +--------------+---------------+
                         |
                         |
                   Entity migration
                         |
                         v
          +--------------+---------------+
          |                              |
          |   Drupal entity              |
          |                              |
          +------------------------------+

```

### Fan out

Once data is migrated into the stage table, a single migration need *not* be responsible for creating Drupal entities. Multiple migrations may be used to fan out the process. For user profiles, for example, different migrations can be created for each profile "fragment":

```
          +------------------------------+
          |                              |
          |   Digital Measures API       |
          |                              |
          +--+-----------+-----------+---+
             |           |           |
Remote       |           |           |
+----------------------------------------------------+
Local        |           |           |
             v           |           v
      Migration1    Migration2    Migration3
             |           |           |
             v           v           v
          +--+-----------+-----------+---+
          |                              |
          |   Stage table                |
          |                              |
          +--+-----------+-----------+---+
             |           |           |
             v           |           v
      MigrationA    MigrationB    MigrationC
             |           |           |
             v           v           v
          +--+--+     +--+--+     +--+---+
          |     |     |     |     |      |
          |  Profile fragment migrations |
          |     |     |     |     |      |
          +-----+     +-----+     +------+
```

### Multistage

For the largest of data sets, you can use both the user and profile stage tables to create a multi-stage migration. This stages usernames to the table, then profile fragments to the next, then finally creates profile entities:

```
                 +------------------------------+
                 |                              |
           +-----+   Digital Measures API       |
           |     |                              |
           |     +--+-----------+-----------+---+
           |        |           |           |
Remote     |        |           |           |
+-----------------------------------------------------------+
Local      |        |           |           |
           |        v           |           v
           | Migration1    Migration2    Migration3
           |        |           |           |
           |        v           v           v
           |     +--+-----------+-----------+---+
           |     |                              |
           |     |   User stage table           |
           |     |                              |
           |     +--------------+---------------+
           |                    |
           |                    |
           +-----> Profile fragment migration
                                |
                                v
                 +--------------+---------------+
                 |                              |
                 |   Profile stage table        |
                 |                              |
                 +---+-----------+-----------+--+
                     |           |           |
                     v           |           v
              MigrationA    MigrationB    MigrationC
                     |           |           |
                     v           v           v
                  +--+--+     +--+--+     +--+---+
                  |     |     |     |     |      |
                  |  Profile fragment migrations |
                  |     |     |     |     |      |
                  +-----+     +-----+     +------+
```