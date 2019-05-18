<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\commerce\ConditionGroup;
use Drupal\commerce_installments\Plugin\InstallmentPlanMethodPluginCollection;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the installment plan method entity class.
 *
 * @ConfigEntityType(
 *   id = "installment_plan_method",
 *   label = @Translation("Installment plan method"),
 *   label_collection = @Translation("Installment plan methods"),
 *   label_singular = @Translation("Installment plan method"),
 *   label_plural = @Translation("Installment plan methods"),
 *   label_count = @PluralTranslation(
 *     singular = "@count installment plan method",
 *     plural = "@count installment plan methods",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_installments\InstallmentPlanMethodListBuilder",
 *     "storage" = "Drupal\commerce_installments\InstallmentPlanMethodStorage",
 *     "form" = {
 *       "add" = "Drupal\commerce_installments\Form\InstallmentPlanMethodForm",
 *       "edit" = "Drupal\commerce_installments\Form\InstallmentPlanMethodForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce installment plan methods",
 *   config_prefix = "installment_plan_methods",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "status",
 *     "plugin",
 *     "configuration",
 *     "conditions",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/installment_plan_methods/add",
 *     "edit-form" = "/admin/commerce/config/installment_plan_methods/manage/{installment_plan_method}",
 *     "delete-form" = "/admin/commerce/config/installment_plan_methods/manage/{installment_plan_method}/delete",
 *     "collection" =  "/admin/commerce/config/installment_plan_methods"
 *   }
 * )
 */
class InstallmentPlanMethod extends ConfigEntityBase implements InstallmentPlanMethodInterface {

  /**
   * The installment plan method ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The installment plan method label.
   *
   * @var string
   */
  protected $label;

  /**
   * The installment plan method weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The plugin collection that holds the installment plan method plugin.
   *
   * @var \Drupal\commerce_installments\Plugin\InstallmentPlanMethodPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $weight;
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
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    $plugin_manager = \Drupal::service('plugin.manager.commerce_condition');
    $conditions = [];
    foreach ($this->conditions as $condition) {
      $conditions[] = $plugin_manager->createInstance($condition['plugin'], $condition['configuration']);
    }
    return $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    $conditions = $this->getConditions();
    if (!$conditions) {
      // installment plan methods without conditions always apply.
      return TRUE;
    }
    $method_conditions = array_filter($conditions, function ($condition) {
      /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
      return $condition->getEntityTypeId() == 'commerce_order';
    });
    $method_conditions = new ConditionGroup($method_conditions, 'AND');

    return $method_conditions->evaluate($order);
  }

  /**
   * Gets the plugin collection that holds the installment plan method plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce_installments\Plugin\InstallmentPlanMethodPluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_installment_plan_methods');
      $this->pluginCollection = new InstallmentPlanMethodPluginCollection($plugin_manager, $this->plugin, $this->configuration, $this->id);
    }
    return $this->pluginCollection;
  }

}
