<?php

namespace Drupal\entity_pilot\EntityResolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\entity_pilot\ExistsPluginManagerInterface;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolves entities from unsaved data and existing entities.
 */
class UnsavedUuidResolver implements UnsavedUuidResolverInterface {

  /**
   * Exists plugin manager service.
   *
   * @var \Drupal\entity_pilot\ExistsPluginManagerInterface
   */
  protected $existsPluginManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new UnsavedUuidResolver object.
   *
   * @param \Drupal\entity_pilot\ExistsPluginManagerInterface $plugin_manager
   *   Exists plugin manager service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ExistsPluginManagerInterface $plugin_manager, EntityManagerInterface $entity_manager) {
    $this->existsPluginManager = $plugin_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * Array of unsaved entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $stack = [];

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    if (($normalizer instanceof UuidReferenceInterface) && ($uuid = $normalizer->getUuid($data))) {
      if (isset($this->stack[$uuid])) {
        $unsaved = $this->stack[$uuid];
        if ($existing = $this->existsPluginManager->exists($this->entityManager, $unsaved)) {
          return $existing;
        }
        return $unsaved;
      }
    }
    return NULL;
  }

  /**
   * Adds an entity to the stack of unresolved entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add to the stack.
   *
   * @return $this
   *   Instance method was called on.
   */
  public function add(EntityInterface $entity) {
    $this->stack[$entity->uuid()] = $entity;
    return $this;
  }

}
