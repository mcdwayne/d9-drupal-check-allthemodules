<?php

namespace Drupal\bundle_override\Manager\Objects;

/**
 * Interface BundleOverrideObjectsInterface.
 *
 * @package Drupal\bundle_override\Manager\Objects
 */
interface BundleOverrideObjectsInterface {

  /**
   * Return the bundle.
   *
   * @return string
   *   The bundle.
   */
  public static function getStaticBundle();

  /**
   * Return the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public static function getStaticEntityTypeId();

  /**
   * Return the storage instance.
   *
   * @return \Drupal\Core\Entity\ContentEntityStorageInterface
   *   The storage entity.
   */
  public static function getOverridedStorage();

}
