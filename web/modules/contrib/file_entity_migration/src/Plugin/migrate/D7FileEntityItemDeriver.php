<?php

namespace Drupal\file_entity_migration\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for file entities.
 */
class D7FileEntityItemDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache;

  /**
   * The CCK plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * Already-instantiated field plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface[]
   */
  protected $fieldPluginCache;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * D7FileEntityItemDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface $cck_manager
   *   The CCK plugin manager.
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_manager
   *   The field plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($base_plugin_id, MigrateCckFieldPluginManagerInterface $cck_manager, MigrateFieldPluginManagerInterface $field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->cckPluginManager = $cck_manager;
    $this->fieldPluginManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.migrate.cckfield'),
      $container->get('plugin.manager.migrate.field'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $types = static::getSourcePlugin('d7_file_entity_type');

    try {
      $types->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    $fields = [];
    try {
      $source_plugin = static::getSourcePlugin('d7_field_instance');
      $source_plugin->checkRequirements();

      foreach ($source_plugin as $row) {
        if ($row->getSourceProperty('entity_type') == 'file') {
          $fields[$row->getSourceProperty('bundle')][$row->getSourceProperty('field_name')] = $row->getSource();
        }
      }
    }
    catch (RequirementsException $e) {
      // No fields, no problem. We can keep going.
    }

    try {
      foreach ($types as $row) {
        /** @var \Drupal\migrate\Row $row */
        $values = $base_plugin_definition;
        $bundle_name = $row->getSourceProperty('type');

        // Check that there is a corresponding media bundle.
        /** @var \Drupal\media\MediaTypeInterface $media_type */
        $media_type = $this->entityTypeManager->getStorage('media_type')
          ->load($bundle_name);
        if (empty($media_type)) {
          continue;
        }

        // Create the migration derivative.
        $values['source']['type'] = $bundle_name;
        $values['label'] = t('@label (@type)', [
          '@label' => $base_plugin_definition['label'],
          '@type' => $bundle_name,
        ]);
        $values['destination']['bundle'] = $bundle_name;
        $values['process'][$media_type->getSource()->getConfiguration()['source_field'] . '/target_id'] = 'fid';

        /** @var \Drupal\migrate\Plugin\Migration $migration */
        $migration = \Drupal::service('plugin.manager.migration')
          ->createStubMigration($values);
        if (isset($fields[$bundle_name])) {
          foreach ($fields[$bundle_name] as $field_name => $info) {
            $field_type = $info['type'];
            try {
              $plugin_id = $this->fieldPluginManager->getPluginIdFromFieldType($field_type, ['core' => 7], $migration);
              if (!isset($this->fieldPluginCache[$field_type])) {
                $this->fieldPluginCache[$field_type] = $this->fieldPluginManager->createInstance($plugin_id, ['core' => 7], $migration);
              }
              $this->fieldPluginCache[$field_type]
                ->processFieldValues($migration, $field_name, $info);
            }
            catch (PluginNotFoundException $ex) {
              try {
                $plugin_id = $this->cckPluginManager->getPluginIdFromFieldType($field_type, ['core' => 7], $migration);
                if (!isset($this->cckPluginCache[$field_type])) {
                  $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($plugin_id, ['core' => 7], $migration);
                }
                $this->cckPluginCache[$field_type]
                  ->processCckFieldValues($migration, $field_name, $info);
              }
              catch (PluginNotFoundException $ex) {
                $migration->setProcessOfProperty($field_name, $field_name);
              }
            }
          }
        }
        $this->derivatives[$bundle_name] = $migration->getPluginDefinition();
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
    }
    return $this->derivatives;
  }

}
