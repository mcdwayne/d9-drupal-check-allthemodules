<?php

namespace Drupal\improvements\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_entity_metatag"
 * )
 */
class D7EntityMetatag extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $d7_database = Database::getConnection('default', $row->getSourceProperty('key'));

    $metatag = $d7_database
      ->select('metatag')
      ->fields('metatag', ['data'])
      ->condition('metatag.entity_type', $this->configuration['entity_type'])
      ->condition('metatag.entity_id', $value)
      ->execute()
      ->fetchField();

    return $metatag ? $metatag : 'a:0:{}';
  }

}
