<?php

namespace Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\node;

use Drupal\bundle_override\Manager\Objects\BundleOverrideObjectsInterface;
use Drupal\bundle_override\Tools\BundleOverrideEntityTrait;
use Drupal\node\Entity\Node;

/**
 * Class NodeB.
 *
 * @package Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\node
 */
abstract class NodeB extends Node implements BundleOverrideObjectsInterface {

  use BundleOverrideEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function getStaticEntityTypeId() {
    return 'node';
  }

  /**
   * {@inheritdoc}
   */
  public static function getOverridedStorage() {
    return static::getOverridedStorageFromClassName(__NAMESPACE__ . '\\NodeBStorage');
  }

}
