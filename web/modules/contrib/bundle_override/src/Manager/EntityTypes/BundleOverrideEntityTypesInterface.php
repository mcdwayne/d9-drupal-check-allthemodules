<?php

namespace Drupal\bundle_override\Manager\EntityTypes;

/**
 * Interface BundleOverrideEntityTypesInterface.
 *
 * @package Drupal\bundle_override\Manager\EntityTypes
 */
interface BundleOverrideEntityTypesInterface {

  /**
   * Return the content storage object redefining the method to get the entity.
   *
   * @return \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   *   The SqlContentEntityStorage.
   */
  public function getStorageClass();

  /**
   * Return the default class to override.
   *
   * Ex for node: '\Drupal\node\Entity\Node'
   *
   * @return string
   *   The default class.
   */
  public function getDefaultEntityClass();

  /**
   * Return the redefiner class.
   *
   * Ex for node:
   *  '\Drupal\bundle_override\Plugin\bundle_override\EntityTypes\node\NodeB'
   *
   * @return string
   *   The redefiner class.
   */
  public function getRedefinerClass();

  /**
   * Return the plugin manager service id.
   *
   * There is no need to define service in yml.
   * Ex for node : 'bundle_override.node_plugin_manager'
   *
   * @return string
   *   The service id.
   */
  public function getServiceId();

}
