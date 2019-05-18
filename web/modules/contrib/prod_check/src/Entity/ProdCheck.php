<?php

namespace Drupal\prod_check\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\prod_check\CheckPluginCollection;

/**
 * Defines the configured prod check entity.
 *
 * @ConfigEntityType(
 *   id = "prod_check",
 *   label = @Translation("Production check"),
 *   admin_permission = "administer production check",
 *   handlers = {
 *     "form" = {
 *       "edit" = "Drupal\prod_check\Form\CheckEditForm",
 *       "disable" = "Drupal\prod_check\Form\CheckDisableForm",
 *       "enable" = "Drupal\prod_check\Form\CheckEnableForm"
 *     },
 *     "list_builder" = "Drupal\prod_check\CheckListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/prod-check/checks/configure/{prod_check}",
 *     "collection" = "/admin/config/system/prod-check/checks",
 *     "disable" = "/admin/config/system/prod-check/checks/configure/{prod_check}/disable",
 *     "enable" = "/admin/config/system/prod-check/checks/configure/{prod_check}/enable"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *   }
 * )
 */
class ProdCheck extends ConfigEntityBase implements ProdCheckConfigInterface, EntityWithPluginCollectionInterface {

  /**
   * The name (plugin ID) of the check.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the check.
   *
   * @var string
   */
  protected $label;

  /**
   * The configuration of the check.
   *
   * @var array
   */
  protected $configuration = array();

  /**
   * The plugin ID of the check.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that stores action plugins.
   *
   * @var \Drupal\prod_check\CheckPluginCollection
   */
  protected $pluginCollection;

  /**
   * Encapsulates the creation of the action's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The action's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new CheckPluginCollection(\Drupal::service('plugin.manager.prod_check'), $this->plugin, $this->configuration);
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return array('configuration' => $this->getPluginCollection());
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin_id) {
    $this->plugin = $plugin_id;
    $this->getPluginCollection()->addInstanceId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->getPlugin()->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return $this->getPlugin() instanceof ConfigurablePluginInterface;
  }

  /**
   * Fetches the category of the plugin
   */
  public function getCategory() {
    return $this->getPlugin()->category();
  }

}
