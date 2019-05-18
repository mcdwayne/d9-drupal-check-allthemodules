<?php

namespace Drupal\entity_access_audit\Dimensions;

use Drupal\entity_access_audit\AccessDimensionInterface;

/**
 * A dimension for if the user is the entity owner or not.
 */
class EntityOwnerDimension implements AccessDimensionInterface {

  /**
   * If the user is the entity owner or not.
   *
   * @var bool
   */
  protected $isEntityOwner;

  /**
   * Create an instance of the EntityOwnerDimension.
   */
  public function __construct($isEntityOwner) {
    $this->isEntityOwner = $isEntityOwner;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User is entity owner');
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensionValue() {
    return $this->isEntityOwner ? t('Own Content') : t('Any content');
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->isEntityOwner ? '1' : '0';
  }

  /**
   * If this dimension assigns the entity owner.
   *
   * @return bool
   *   If the dimension assigns the entity owner.
   */
  public function isEntityOwner() {
    return $this->isEntityOwner;
  }

}
