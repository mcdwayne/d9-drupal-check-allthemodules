<?php

namespace Drupal\flexiform_wizard\FormEntity;

use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

/**
 * Provides a tempstore aware form entity manager.
 */
class TempstoreAwareFlexiformFormEntityManager extends FlexiformFormEntityManager {

  /**
   * Tempstore Factory for keeping track of entities.
   *
   * @var \Drupal\user\SharedTempStore|\Drupal\user\PrivateTempStore
   */
  protected $tempstore;

  /**
   * Set the tempstore.
   *
   * @param \Drupal\user\PrivateTempStore|\Drupal\user\SharedTempStore $tempstore
   *   Either Drupal\user\PrivateTempStore or Drupal\user\SharedTempStore.
   */
  protected function setTempstore($tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * Get the tempstore.
   *
   * @return \Drupal\user\PrivateTempStore|\Drupal\user\SharedTempStore
   *   The tempstore.
   */
  protected function getTempstore() {
    return $this->tempstore;
  }

  /**
   * {@inheritdoc}
   */
  protected function initFormEntities(array $provided = []) {
    $stored_entities = $this->tempstore->get('form_entities');
    $provided = $stored_entities + $provided;
    parent::initFormEntities($provided);
  }

}
