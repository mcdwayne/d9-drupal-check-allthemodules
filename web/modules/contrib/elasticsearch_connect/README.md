## INTRODUCTION 
 
 Provides a number of utility and helper to access/index Drupal entities.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/elasticsearch_connect

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/elasticsearch_connect


## INSTALLATION

This module needs to be installed via Composer, which will download the required libraries.

1. Add the Drupal Packagist repository

    ```sh
    composer config repositories.drupal composer https://packages.drupal.org/8
    ```
This allows Composer to find Elasticsearch Connect and the other Drupal modules.

2. Download Elasticsearch Connect

   ```sh
   composer require drupal/elasticsearch_connect
   ```

See https://www.drupal.org/node/2404989 for more information.

## CONFIGURATION
1. Make sure you have a running Elasticsearch cluster

See https://www.elastic.co/products/elasticsearch for details about installing/running a cluster.

2. Add cluster settings in your settings.php file

	```php
	/**
	 * Elasticsearch connect settings:
	 */
	$config['elasticsearch_connect.settings']['host'] = '10.0.2.2';
	$config['elasticsearch_connect.settings']['port'] = '9200';
	$config['elasticsearch_connect.settings']['scheme'] = 'http';
	```
	
 See config/install/elasticsearch_connect.settings.yml for more params.

3. Add indexation/mapping settings for your entity types via hooks

See doc in elasticsearch_connect.api.inc file

4. Create your index using drush command `drush esc-create my_first_index_id`

5. Add index settings in your settings.php file

	```php
	$config['elasticsearch_connect.settings']['index_id'] = 'my_unique_index_id';
	```
6. Index/Map your contents usiing drush command `drush esc-index`
