<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove an alias that match a given path.
 *
 * @MigrateProcessPlugin(
 *  id = "remove_url_alias_for_path"
 * )
 */
class RemoveUrlAliasForPath extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Alias storage service.
   *
   * @var \Drupal\pathauto\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration, AliasStorageInterface $aliasStorage) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->aliasStorage = $aliasStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $migration,
      $container->get('path.alias_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // By default we lookup by source column.
    $valid_fields = ['source', 'alias'];
    $field = 'source';

    if (isset($this->configuration['field'])) {
      $field = $this->configuration['field'];
    }

    // When lookup field is invalid we fallback to use source field.
    if (!in_array($field, $valid_fields)) {
      $field = 'source';
    }

    if (!empty($value) && is_string($value)) {
      // The source path must start with leading slash.
      $path = $value;
      if ($path[0] != '/') {
        $path = '/' . $path;
      }

      // Delete any URL alias disregard the language that match source path.
      $result = $this->aliasStorage->delete([
        $field => $path,
      ]);
    }

    return $value;
  }

}
