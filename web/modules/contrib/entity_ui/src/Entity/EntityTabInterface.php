<?php

namespace Drupal\entity_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining Entity tab entities.
 */
interface EntityTabInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the target entity type ID.
   *
   * @return string
   *  The entity type ID that this entity tab is on.
   */
  public function getTargetEntityTypeID();

  /**
   * Gets the permissions this tab provides.
   *
   * @return array
   *  An array of permissions.
   */
  public function getPermissions();

  /**
   * Gets the path component for this tab.
   *
   * @return string
   *  The path component that is appended to the target entity's canonical URL.
   */
  public function getPathComponent();

  /**
   * Gets the route name for the route this tab provides.
   *
   * @return string
   *   The route name.
   */
  public function getRouteName();

  /**
   * Gets the page title for this tab.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *  The target entity of the tab entity.
   *
   * @return string
   *  The path component that is appended to the target entity's canonical URL.
   */
  public function getPageTitle(EntityInterface $target_entity);

  /**
   * Gets the tab title for this tab.
   *
   * @return string
   *  The path component that is appended to the target entity's canonical URL.
   */
  public function getTabTitle();

  /**
   * Gets the content plugin ID for this tab.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginID();

  /**
   * Returns the settings for the content plugin.
   *
   * @return array
   *  The plugin settings.
   */
  public function getPluginConfiguration();

  /**
   * Get the link type plugin for this flag.
   *
   * @return \Drupal\entity_ui\Plugin\EntityTabContentInterface
   *   The link type plugin for the flag.
   */
  public function getContentPlugin();

}
