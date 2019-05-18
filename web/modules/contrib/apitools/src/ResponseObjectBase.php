<?php

namespace Drupal\apitools;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Api object plugins.
 *
 * TODO: Method for createFromEntity, does a look up to find which object.
 * TODO: Distinguish between an actual object that represents an entity and one that just has a helper entity and needs to hide the ID
 */
abstract class ResponseObjectBase extends PluginBase implements ResponseObjectInterface, SerializableObjectInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  use ExtensibleObjectTrait;

  use SerializableObjectTrait;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ResponseObjectManager;
   */
  protected $objectManager;

  /**
   * @var EntityInterface;
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ResponseObjectManager $object_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->objectManager = $object_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.response_object'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    $this->id = $this->entity->id();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get the id of the base entity if applicable.
   *
   * @return int|null|string
   */
  public function getId() {
    if ($entity = $this->getEntity()) {
      return $entity->id();
    }
    return NULL;
  }

  /**
   * Set object with values from array if they are in the Iterator.
   *
   * @see SerializableObjectInterface::getFields()
   *
   * @param array $values
   *   Array of values, if keys are not in Iterator they are ignored.
   */
  public function setValues(array $values = []) {
    foreach ($this as $field_name) {
      if (!isset($values[$field_name])) {
        continue;
      }
      $this->set($field_name, $values[$field_name]);
    }
    return $this;
  }

  /**
   * Save base entity if applicable.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @return $this
   */
  public function save() {
    if ($entity = $this->getEntity()) {
      $entity->validate();
      $entity->save();
    }
    return $this;
  }
}
