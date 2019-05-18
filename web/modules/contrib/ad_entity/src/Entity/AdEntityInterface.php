<?php

namespace Drupal\ad_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Advertising entities.
 */
interface AdEntityInterface extends ConfigEntityInterface {

  /**
   * Get the corresponding Advertisement type plugin.
   *
   * @return \Drupal\ad_entity\Plugin\AdTypeInterface
   *   An instance of the Advertisement type plugin.
   */
  public function getTypePlugin();

  /**
   * Get the corresponding Advertisement view handler plugin.
   *
   * @return \Drupal\ad_entity\Plugin\AdViewInterface
   *   An instance of the Advertisement view plugin handler.
   */
  public function getViewPlugin();

  /**
   * Get a list of backend context data for this Advertising entity.
   *
   * In contrast to AdContextManager::getContextDataForEntity(),
   * this method also includes context data from third party providers.
   *
   * @return array
   *   The list of available backend context data for the Advertising entity.
   */
  public function getContextData();

  /**
   * Get a list of backend context data for a given plugin id and this entity.
   *
   * In contrast to AdContextManager::getContextDataForPluginAndEntity(),
   * this method also includes context data from third party providers.
   *
   * @param string $plugin_id
   *   The context plugin id.
   *
   * @return array
   *   The list of available backend context data for the given plugin id.
   */
  public function getContextDataForPlugin($plugin_id);

  /**
   * Get a targeting collection from the backend context data.
   *
   * The returned object is not holding any reference to the
   * given Advertising entity. If you modify the targeting collection
   * instance, it won't automatically apply to the Advertising entity.
   * This is due to the fact that context data is based on arrays,
   * which all might be added, removed or changed in arbitrary ways.
   *
   * @return \Drupal\ad_entity\TargetingCollection
   *   The targeting collection from the backend context data.
   */
  public function getTargetingFromContextData();

}
