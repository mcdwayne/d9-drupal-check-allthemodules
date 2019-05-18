<?php

namespace Drupal\improvements\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_entity_path_alias"
 * )
 */
class D7EntityPathAlias extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $d7_database = Database::getConnection('default', $row->getSourceProperty('key'));
    $path_alias = $d7_database
      ->select('url_alias', 'alias')
      ->fields('alias', ['alias'])
      ->condition('alias.source', str_replace('%', $value, $this->configuration['path_pattern']))
      ->execute()
      ->fetchField();

    if ($path_alias) {
      /** @var AliasStorageInterface $path_alias_storage */
      $path_alias_storage = \Drupal::service('path.alias_storage');
      if (!$path_alias_storage->aliasExists('/' . $path_alias, 'ru')) {
        return [
          'alias' => '/' . $path_alias,
          'pathauto' => FALSE,
        ];
      }
    }
  }

}
