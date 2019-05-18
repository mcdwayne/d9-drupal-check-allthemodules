<?php

namespace Drupal\akamai;

/**
 * Wrapper around the optional key.provider service.
 */
class KeyProvider implements KeyProviderInterface {

  /**
   * The key repository service if valid.
   *
   * @var \Drupal\key\KeyRepositoryInterface|null
   */
  protected $keyRepository;

  /**
   * Creates a new key provider service.
   *
   * @param mixed $key_repository
   *   Optional key.repository service (from key module).
   */
  public function __construct($key_repository) {
    $this->keyRepository = $key_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function hasKeyRepository() {
    return isset($this->keyRepository);
  }

  /**
   * {@inheritdoc}
   */
  public function getKey($key) {
    if (!$this->hasKeyRepository()) {
      throw new \Exception('Missing key.repository service. Ensure key module is enabled.');
    }

    $key_entity = $this->keyRepository->getKey($key);
    if (isset($key_entity)) {
      return $key_entity->getKeyValue();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys() {
    if (!$this->hasKeyRepository()) {
      throw new \Exception('Missing key.repository service. Ensure key module is enabled.');
    }

    return $this->keyRepository->getKeys();
  }

}
