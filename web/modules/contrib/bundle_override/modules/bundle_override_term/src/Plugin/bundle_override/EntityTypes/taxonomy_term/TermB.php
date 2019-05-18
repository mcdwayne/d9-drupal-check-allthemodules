<?php

namespace Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\taxonomy_term;

use Drupal\bundle_override\Manager\Objects\BundleOverrideObjectsInterface;
use Drupal\bundle_override\Tools\BundleOverrideEntityTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Class TermB.
 *
 * @package Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\taxonomy_term
 */
abstract class TermB extends Term implements BundleOverrideObjectsInterface {

  use BundleOverrideEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function getStaticEntityTypeId() {
    return 'taxonomy_term';
  }

  /**
   * Return the TermBStorage.
   *
   * @return TermBStorage
   *   The TermBStorage.
   */
  public static function getOverridedStorage() {
    return static::getOverridedStorageFromClassName(__NAMESPACE__ . '\\TermBStorage');
  }

}
