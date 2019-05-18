<?php


namespace Drupal\digitalmeasures_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\digitalmeasures_migrate\Plugin\migrate\source\UserStaging as UserStaging_Source;

/**
 * Provides a migration source for DM users in the staging table
 *
 * @MigrateSource(
 *   id = "digitalmeasures_api_user",
 *   source_module = "digitalmeasures_migrate"
 * )
 */
class User extends UserStaging_Source {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $username = $row->getSourceProperty('username');

    $endpoint = isset($this->configuration['beta']) ? $this->configuration['beta'] : -1;

    $xml = $this->digitalMeasuresApi->getUser($username, $this->configuration['schema_key'], $endpoint);

    $row->setSourceProperty('user_xml', $xml);

    return parent::prepareRow($row);
  }

}
