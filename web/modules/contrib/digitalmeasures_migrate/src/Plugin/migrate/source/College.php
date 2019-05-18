<?php


namespace Drupal\digitalmeasures_migrate\Plugin\migrate\source;

use Drupal\digitalmeasures_migrate\DigitalMeasuresApiServiceInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides a migration source for basic DM profiles by college.
 *
 * This plugin provides a preconfigured Digital Measures source for users
 * filtered by college. Simply specify the college as a URL encoded string in
 * the entry_key:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api_college
 *   schema_key: MY_SCHEMA_KEY
 *   entry_key: Example+College+of+dubious+things
 * @endcode
 *
 * This plugin is the equivalent of:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api
 *   resource: User
 *   schema_key: MY_SCHEMA_KEY
 *   beta: 'yes'
 *   index_key: COLLEGE
 *   entry_key: Example+College+of+dubious+things
 *   item_selector: /Users/User
 *   fields:
 *    -
 *     name: username
 *     label: Username
 *     selector: @entryKey
 *    -
 *     name: userId
 *     label: 'User ID'
 *     selector: '@*[local-name()="userId"]'
 *   ids:
 *     username:
 *       type: string
 * @endcode
 *
 * See the constructor if you want to build your own convenience DM source.
 *
 * @MigrateSource(
 *   id = "digitalmeasures_api_college",
 *   source_module = "digitalmeasures_migrate"
 * )
 */
class College extends DigitalMeasuresApi {

  /**
   * User constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              MigrationInterface $migration,
                              DigitalMeasuresApiServiceInterface $digitalMeasuresApiService) {
    $configuration['resource'] = 'User';
    $configuration['index_key'] = 'COLLEGE';
    $configuration['item_selector'] = '/Users/User';

    $configuration['fields'][] = [
      'name' => 'username',
      'label' => 'Username',
      'selector' => '@username',
    ];

    $configuration['fields'][] = [
      'name' => 'userId',
      'label' => 'User ID',
      'selector' => '@*[local-name()="userId"]',
    ];

    $configuration['ids']['username']['type'] = 'string';
    $configuration['track_changes'] = 1;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $digitalMeasuresApiService);
  }

}
