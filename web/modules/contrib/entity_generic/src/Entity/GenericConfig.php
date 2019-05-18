<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the entity type class.
 */
abstract class GenericConfig extends ConfigEntityBase implements GenericConfigInterface {

  /**
   * The machine name of this entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the entity type.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the entity type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

}
