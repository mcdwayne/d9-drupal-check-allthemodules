<?php

namespace Drupal\migrate_override\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_override\OverrideManagerService;
use Drupal\migrate_override\OverrideManagerServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentEntityOverride.
 *
 * @MigrateDestination(
 *   id = "entity_override",
 *   deriver = "Drupal\migrate_override\Plugin\Derivative\MigrateEntityOverride"
 * )
 */
class ContentEntityOverride extends EntityContentBase {

  /**
   * The override manager.
   *
   * @var \Drupal\migrate_override\OverrideManagerServiceInterface
   */
  protected $overrideManager;

  /**
   * Constructs a ContentEntityOverride object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\migrate_override\OverrideManagerServiceInterface $override_manager
   *   The override manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager, OverrideManagerServiceInterface $override_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_manager, $field_type_manager);
    $this->overrideManager = $override_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type)),
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('migrate_override.override_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    // Remove "entity_override:".
    return substr($plugin_id, 16);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    if (!$entity instanceof ContentEntityInterface) {
      throw new MigrateException("Entity Override only works with content entities");
    }
    if ($this->overrideManager->entityBundleEnabled($entity)) {
      $new_row = $row->cloneWithoutDestination();
      foreach ($row->getDestination() as $field_name => $field_value) {
        if ($this->overrideManager->getEntityFieldStatus($entity, $field_name) !== OverrideManagerService::ENTITY_FIELD_OVERRIDDEN) {
          $new_row->setDestinationProperty($field_name, $row->getDestinationProperty($field_name));
        }
      }
      foreach ($row->getEmptyDestinationProperties() as $empty_destination_property) {
        if ($this->overrideManager->getEntityFieldStatus($entity, $empty_destination_property) !== OverrideManagerService::ENTITY_FIELD_OVERRIDDEN) {
          $new_row->setEmptyDestinationProperty($field_name);
        }
      }
      $row = $new_row;
    }
    return parent::updateEntity($entity, $row);
  }

}
