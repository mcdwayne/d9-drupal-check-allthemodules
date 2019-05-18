<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RedirectMigrationLookup' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "redirect_migration_lookup"
 * )
 */
class RedirectMigrationLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migration_ids = $this->configuration['migration'];

    // By default add internal prefix to processed path.
    if (!isset($this->configuration['prefix_internal'])) {
      $this->configuration['prefix_internal'] = TRUE;
    }

    if (!is_array($migration_ids)) {
      $migration_ids = [$migration_ids];
    }

    // Remove path schema.
    $path = str_replace('internal:/', '', $value);

    // Ignore absolute URLS, that not need internal path mapping.
    if (UrlHelper::isExternal($path)) {
      return $value;
    }

    // Skip invalid path.
    if (!UrlHelper::isValid($path)) {
      return $value;
    }

    // Determine the redirect entity_id.
    $url_parts = UrlHelper::parse($path);
    $path_parts = explode('/', $url_parts['path']);
    // Determine the entity type from first argument.
    $entity_type = $path_parts[0];

    // Get the entity ID from appropriate argument.
    switch ($entity_type) {
      case 'node':
      case 'user':
      case 'file':
        $arg_delta = 1;
        break;

      case 'taxonomy':
        $arg_delta = 2;
        break;

      // Ignore not implemented entity types mapping.
      default:
        $source_id = FALSE;
        break;
    }

    $source_id = $path_parts[$arg_delta];
    // Not known entity type, return original path.
    if (!$source_id) {
      return $value;
    }

    $entity_type_id_key = [
      'file' => 'fid',
      'node' => 'nid',
      'taxonomy' => 'tid',
      'user' => 'uid',
    ];

    $destination_entity_type_path = [
      'group' => 'group/[id]',
      'media' => 'media/[id]',
      'node' => 'node/[id]',
      'taxonomy' => 'taxonomy/term/[id]',
      'user' => 'user/[id]',
      'webform' => 'webform/[id]',
    ];

    $destination_path = FALSE;
    $migrations = $this->migrationPluginManager->createInstances($migration_ids);
    // @var \Drupal\migrate\Plugin\Migration $migration
    foreach ($migrations as $migration_id => $migration) {
      // @var \Drupal\migrate\Plugin\MigrateSourceInterface $source
      $source = $migration->getSourcePlugin();
      $id_keys = array_keys($source->getIds());

      // Skip lookup on migrations that do not match source entity type that we
      // infer from source id key due lack of property that indicate the D7
      // origin entity type.
      if (!in_array($entity_type_id_key[$entity_type], $id_keys)) {
        continue;
      }

      // Skip the current migration.
      if ($migration_id == $this->migration->id()) {
        continue;
      }

      // Source id must include the source ID key.
      $source_id_values[$entity_type_id_key[$entity_type]] = $source_id;

      // Lookup the target redirect ID.
      if ($destination_id = $migration->getIdMap()->lookupDestinationId($source_id_values)) {
        // Build the D8 target path.
        $destination_config = $migration->getDestinationConfiguration();
        list ($plugin_name, $destination_entity_type) = explode(':', $destination_config['plugin']);

        // Skip non entity target plugins that we cannot build
        // the D8 entity view paths.
        if (strpos($plugin_name, 'entity') !== FALSE) {
          // TODO: Improve with D8 entiy load and use of Entity::toUrl() method.
          // Check that we have a entity path pattern.
          if (isset($destination_entity_type_path[$destination_entity_type])) {
            $destination_path = $destination_entity_type_path[$destination_entity_type];
            // Replace the ID placeholder with entity ID.
            $destination_path = str_replace('[id]', $destination_id[0], $destination_path);
          }
        }

        // Stop on first match.
        break;
      }
    }

    // On failed lookup skip migrate the redirect row.
    if (empty($destination_path)) {
      $message = $this->t(
        "Skipped redirect to '@path' due missing lookup with D8 entity.",
        ['@path' => $value]
      );

      throw new MigrateSkipRowException($message);
    }

    // Add the internal prefix.
    if ($this->configuration['prefix_internal']) {
      $destination_path = 'internal:/' . $destination_path;
    }

    return $destination_path;
  }

}
