<?php

namespace Drupal\deploy_key;

use Codeaken\SshKey\SshKeyPair;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * KeyManager service.
 */
class KeyManager {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * KeyManager constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * Get a key for an entity, and generate if we have to.
   *
   * Will also force regenerate if specified.
   */
  public function generateKeyForEntity(EntityInterface $entity, $force_regenerate = FALSE) {
    // First see if we have it.
    $key = $this->getKey($entity);
    if (NULL !== $key && !$force_regenerate) {
      return $key;
    }
    $key = $this->generateKey($entity);
    $this->state->set($this->getStorageKey($entity), $key);
    return $key;
  }

  /**
   * Generates the key.
   */
  protected function generateKey(EntityInterface $entity) {
    $storage_key = $this->getStorageKey($entity);
    // Go ahead and generate the key. We use the same recommendation as github
    // does, 4096 bit.
    // https://help.github.com/articles/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent/
    return SshKeyPair::generate(4096);
  }

  /**
   * Get the actual key, if it exists.
   */
  public function getKey(EntityInterface $entity) {
    $store_key = $this->getStorageKey($entity);
    return $this->state->get($store_key);
  }

  /**
   * Get the key where we store it.
   */
  public function getStorageKey(EntityInterface $entity) {
    return sprintf('%s_%s', $entity->getEntityTypeId(), $entity->id());
  }

}
