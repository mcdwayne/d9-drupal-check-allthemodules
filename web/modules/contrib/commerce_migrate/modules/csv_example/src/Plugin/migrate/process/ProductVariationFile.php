<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get the fid for the provided filename.
 *
 * Enforces a migration_lookup of the fid for this input filename. If found
 * an array of file information is returned, else a an empty array is returned.
 *
 * Source array:
 * - The SKU.
 * - The filename.
 *
 * Example:
 *
 * @code
 *  process:
 *   file:
 *     plugin: csv_example_product_variation_file
 *     source:
 *       - sku
 *       - filename
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "csv_example_product_variation_file"
 * )
 */
class ProductVariationFile extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration process plugin, configured for lookups in import_image.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The migration process plugin, configured for lookups in import_image.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $migrationPlugin;

  /**
   * Constructs a FieldFile plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\migrate\Plugin\MigrateProcessInterface $migration_plugin
   *   An instance of the 'migration' process plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateProcessInterface $migration_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrationPlugin = $migration_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    // Configure the migration process plugin to look up migrated IDs from
    // a d6 file migration.
    $migration_plugin_configuration = $configuration +
      [
        'migration' => 'csv_example_image',
        'source' => ['sku', 'image'],
      ];

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate.process')->createInstance('migration', $migration_plugin_configuration, $migration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Try to look up the ID of the migrated file. If one cannot be found, it
    // means the file referenced by the current field item did not migrate for
    // some reason -- file migration is notoriously brittle -- and we do NOT
    // want to send invalid file references into the field system (it causes
    // fatal errors), so return an empty item instead.
    if ($fid = $this->migrationPlugin->transform($value, $migrate_executable, $row, $destination_property)) {
      return [
        'target_id' => $fid,
        'description' => '',
        'alt' => '',
        'title' => '',
      ];
    }
    else {
      return [];
    }
  }

}
