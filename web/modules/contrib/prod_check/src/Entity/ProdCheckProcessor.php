<?php

namespace Drupal\prod_check\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\prod_check\ProcessorPluginCollection;

/**
 * Defines the configured prod check processor entity.
 *
 * @ConfigEntityType(
 *   id = "prod_check_processor",
 *   label = @Translation("Production check processor"),
 *   admin_permission = "administer production check",
 *   handlers = {
 *     "form" = {
 *       "edit" = "Drupal\prod_check\Form\ProcessorEditForm",
 *     },
 *     "list_builder" = "Drupal\prod_check\ProcessorListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/prod-check/processors/configure/{prod_check_processor}",
 *     "collection" = "/admin/config/system/prod-check/processors",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *   }
 * )
 */
class ProdCheckProcessor extends ConfigEntityBase implements ProdCheckProcessorConfigInterface, EntityWithPluginCollectionInterface {

  /**
   * The name (plugin ID) of the processor.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the processor.
   *
   * @var string
   */
  protected $label;

  /**
   * The configuration of the processor.
   *
   * @var array
   */
  protected $configuration = array();

  /**
   * The plugin ID of the processor.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that stores processor plugins.
   *
   * @var \Drupal\prod_check\ProcessorPluginCollection
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
      $this->pluginCollection = new ProcessorPluginCollection(\Drupal::service('plugin.manager.prod_check_processor'), $this->plugin, $this->configuration);
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

}
