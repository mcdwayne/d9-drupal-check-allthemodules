<?php

namespace Drupal\wbm2cm\Plugin\migrate\source;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads certain fields from all content entities of a specific type.
 *
 * @MigrateSource(
 *   id = "content_entity",
 *   deriver = "\Drupal\wbm2cm\Plugin\migrate\source\ContentEntityDeriver",
 * )
 */
class ContentEntity extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * ContentEntity constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager) {
    // Merge in default configuration.
    $configuration += [
      'fields' => [
      ],
      'keys' => [
        'id',
      ],
      'include_translations' => TRUE,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->storage = $entity_type_manager->getStorage($this->getDerivativeId());
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * Streams the available entities.
   *
   * @return \Generator
   */
  protected function load() {
    foreach ($this->storage->getQuery()->execute() as $id) {
      yield $this->storage->load($id);
    }
  }

  protected function buildRow(ContentEntityInterface $entity) {
    $row = [];

    foreach ($this->fields() as $field) {
      $items = $entity->get($field);
      $property = $items->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $row[$field] = $items->$property;
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = [];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($this->load() as $entity) {
      array_push($rows, $this->buildRow($entity));

      if ($this->configuration['include_translations']) {
        $languages = array_keys($entity->getTranslationLanguages(FALSE));

        foreach ($languages as $language) {
          $translation = $entity->getTranslation($language);
          array_push($rows, $this->buildRow($translation));
        }
      }
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->storage->getEntityType()->getPluralLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];

    $entity_type = $this->storage->getEntityType();
    foreach ($this->configuration['keys'] as $key) {
      $key = $entity_type->getKey($key);
      $fields[$key] = $key;
    }
    return $fields + array_combine($this->configuration['fields'], $this->configuration['fields']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $entity_type = $this->storage->getEntityType();

    // Always identify each entity by its ID at least.
    $ids = [
      $entity_type->getKey('id') => [
        'type' => 'integer',
      ],
    ];

    // If the entity type supports revisions, identify each entity by their
    // revision ID as well.
    if ($entity_type->isRevisionable()) {
      $revision = $entity_type->getKey('revision');
      $ids[$revision]['type'] = 'integer';
    }

    // If the entity type supports translations, identify each entity by its
    // language code as well.
    if ($entity_type->isTranslatable()) {
      $langcode = $entity_type->getKey('langcode');
      $ids[$langcode]['type'] = 'string';
    }

    return $ids;
  }

}
