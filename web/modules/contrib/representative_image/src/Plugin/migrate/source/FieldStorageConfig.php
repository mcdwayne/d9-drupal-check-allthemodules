<?php

namespace Drupal\representative_image\Plugin\migrate\source;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches representative image fields from the source database.
 *
 * @MigrateSource(
 *   id = "d7_representative_image_field_storage_config",
 *   source_module = "representative_image",
 * )
 */
class FieldStorageConfig extends DrupalSqlBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Fetch representative image fields that have a value.
    return $this->getDatabase()
      ->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', 'representative_image_field_%', 'LIKE')
      ->condition('value', 's:0:"";', '<>')
      ->condition('value', 'i:0;', '<>');
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('The name of the representative image field\'s variable.'),
      'value' => $this->t('The value of the representative image field\'s variable.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    $name = $row->getSourceProperty('name');
    // Variables are made of representative_image_field_[entity type]_[bundle].
    // First let's find a matching entity type from the variable name.
    foreach ($entity_definitions as $entity_type => $definition) {
      if (strpos($name, 'representative_image_field_' . $entity_type . '_') === 0) {
        // Set process values.
        $row->setSourceProperty('entity_type', $entity_type);
        return parent::prepareRow($row);
      }
    }

    // No matching entity type found in destination for this variable. Skipping.
    return FALSE;
  }

}
