<?php

namespace Drupal\markjs_search;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Define MarkJS profile manager.
 */
class MarkjsProfileManager implements MarkjsProfileManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MarkJS profile constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileOptions() {
    $options = [];

    foreach ($this->loadMarkjsProfiles() as $profile_id => $profile) {
      $options[$profile_id] = $profile->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProfile($identifier) {
    return $this->getMarkjsProfileStorage()->load($identifier);
  }

  /**
   * Load MarkJS profiles.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of MarkJS profile entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadMarkjsProfiles() {
    return $this->getMarkjsProfileStorage()->loadMultiple();
  }

  /**
   * Get MarkJS profile storage instance.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getMarkjsProfileStorage() {
    return $this->entityTypeManager->getStorage('markjs_profile');
  }
}
