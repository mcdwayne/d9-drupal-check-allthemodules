<?php

namespace Drupal\bundle_override_user\Plugin\bundle_override\EntityTypes\user;

use Drupal\bundle_override\Manager\Objects\BundleOverrideObjectsInterface;
use Drupal\bundle_override\Tools\BundleOverrideEntityTrait;
use Drupal\user_bundle\Entity\TypedUser;

/**
 * Class UserB.
 *
 * @package Drupal\bundle_override_user\Plugin\bundle_override\EntityTypes\user
 */
abstract class UserB extends TypedUser implements BundleOverrideObjectsInterface {

  use BundleOverrideEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function getStaticEntityTypeId() {
    return 'user';
  }

  /**
   * Return the storage instance.
   *
   * @return UserBStorage
   *   The storage entity.
   */
  public static function getOverridedStorage() {
    return static::getOverridedStorageFromClassName(__NAMESPACE__ . '\\UserBStorage');
  }

}
