<?php

namespace Drupal\multiversion\Plugin\migrate\destination;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "tempstore"
 * )
 */
class TempStore extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * Time to live in seconds until the storage expire.
   *
   * @var int
   */
  protected $expire = 604800;

  /**
   * @var KeyValueStoreExpirableInterface
   */
  protected $tempStore;

  /**
   * @var string
   */
  protected $entityTypeId;

  /**
   * @var string
   */
  protected $entityIdKey;

  /**
   * @var string
   */
  private $entityLanguageKey;

  /** @var \Drupal\Core\Entity\EntityManagerInterface  */
  private $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager'),
      $container->get('keyvalue.expirable')
    );
  }

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param KeyValueExpirableFactoryInterface $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityManagerInterface $entity_manager, KeyValueExpirableFactoryInterface $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    list($entity_type_id) = explode('__', $migration->id());
    $this->entityManager = $entity_manager;
    $entity_type = $this->entityManager->getDefinition($entity_type_id);

    $this->entityTypeId = $entity_type_id;
    $this->entityIdKey = $entity_type->getKey('id');
    $this->entityLanguageKey = $entity_type->getKey('langcode');
    $this->tempStore = $temp_store_factory->get('multiversion_migration_' . $this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $source = $row->getSource();
    $temp_store_id = $source['uuid'];
    $return = [$this->entityIdKey => $source[$this->entityIdKey]];
    if ($this->entityLanguageKey) {
      $return[$this->entityLanguageKey] = $source[$this->entityLanguageKey];
      $temp_store_id .= '.' . $source[$this->entityLanguageKey];
    }
    $this->tempStore->setWithExpire($temp_store_id, $source, $this->expire);
    return $return;
  }

  /**
   * Get whether this destination is for translations.
   *
   * @return bool
   *   Whether this destination is for translations.
   */
  protected function isTranslationDestination() {
    return !empty($this->configuration['translations']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids[$this->entityIdKey] = $this->getDefinitionFromEntity($this->entityIdKey);

    if ($this->isTranslationDestination() && $this->entityLanguageKey) {
      $ids[$this->entityLanguageKey] = $this->getDefinitionFromEntity($this->entityLanguageKey);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [];
  }

  /**
   * Gets the field definition from a specific entity base field.
   *
   * The method takes the field ID as an argument and returns the field storage
   * definition to be used in getIds() by querying the destination entity base
   * field definition.
   *
   * @param string $key
   *   The field ID key.
   *
   * @return array
   *   An associative array with a structure that contains the field type, keyed
   *   as 'type', together with field storage settings as they are returned by
   *   FieldStorageDefinitionInterface::getSettings().
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface::getSettings()
   */
  protected function getDefinitionFromEntity($key) {
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $definitions */
    $definitions = $this->entityManager->getBaseFieldDefinitions($this->entityTypeId);
    $field_definition = $definitions[$key];

    return [
        'type' => $field_definition->getType(),
      ] + $field_definition->getSettings();
  }

}
