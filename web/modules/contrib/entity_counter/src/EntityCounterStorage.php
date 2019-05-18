<?php

namespace Drupal\entity_counter;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage class for entity counter entities.
 */
class EntityCounterStorage extends ConfigEntityStorage {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an EntityCounterStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, StateInterface $state) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface[] $entities */
    // Delete the auxiliary entity counter data.
    foreach ($entities as $entity) {
      $this->state->delete('entity_counter.' . $entity->id());
    }

    parent::doDelete($entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface[] $entities */
    $entities = parent::doLoadMultiple($ids);

    // Load the auxiliary entity counter data and attach it to the entity.
    foreach ($entities as $entity) {
      $value = $this->state->get('entity_counter.' . $entity->id(), $entity->getInitialValue());
      $entity->set('value', is_array($value) ? $value['total'] : $value);
    }

    return $entities;
  }

}
