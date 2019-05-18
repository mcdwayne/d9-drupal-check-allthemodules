<?php


namespace Drupal\digitalmeasures\Plugin\migrate\source;

use Drupal\digitalmeasures\DigitalMeasuresApiServiceInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides a migration source for basic DM profiles.
 *
 * This plugin provides a preconfigured Digital Measures source for profiles.
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api_profile
 *   schema_key: MY_SCHEMA_KEY
 * @endcode
 *
 * This plugin is the equivalent of:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api
 *   resource: SchemaIndex
 *   schema_key: MY_SCHEMA_KEY
 *   beta: 'yes'
 *   index_key: USERNAME
 *   item_selector: /Indexes/Index/IndexEntry
 *   fields:
 *    -
 *     name: username
 *     label: Username
 *     selector: @entryKey
 *   ids:
 *     username:
 *       type: string
 * @endcode
 *
 * See the constructor if you want to build your own convenience DM source.
 *
 * @MigrateSource(
 *   id = "digitalmeasures_api_profile",
 *   source_module = "digitalmeasures"
 * )
 */
class Profile extends DigitalMeasuresApi {

  /**
   * User constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              MigrationInterface $migration,
                              DigitalMeasuresApiServiceInterface $digitalMeasuresApiService) {
    // For faster indexes, we just get an index of usernames.
    $configuration['resource'] = 'SchemaIndex';
    $configuration['index_key'] = 'USERNAME';
    $configuration['item_selector'] = '/Indexes/Index/IndexEntry';

    $configuration['fields'][] = [
      'name' => 'username',
      'label' => 'Username',
      'selector' => '@entryKey',
    ];

    $configuration['ids']['username']['type'] = 'string';
    $configuration['track_changes'] = 1;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $digitalMeasuresApiService);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $username = $row->getSourceProperty('username');

    $endpoint = isset($this->configuration['beta']) ? $this->configuration['beta'] : -1;

    $xml = $this->digitalMeasuresApi->getProfile($username, $this->configuration['schema_key'], $endpoint);

    $row->setSourceProperty('profile_xml', $xml);

    return parent::prepareRow($row);
  }

}
