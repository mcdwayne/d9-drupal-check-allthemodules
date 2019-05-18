<?php

namespace Drupal\commerce_xero\Entity;

use Drupal\commerce_xero\CommerceXeroProcessorPluginInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Commerce Xero Strategy entity type.
 *
 * @ConfigEntityType(
 *   id = "commerce_xero_strategy",
 *   label = @Translation("Commerce Xero Strategy"),
 *   admin_permission = "administer commerce xero",
 *   handlers = {
 *     "access" = "Drupal\commerce_xero\StrategyAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_xero\Controller\StrategyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_xero\Form\StrategyForm",
 *       "edit" = "Drupal\commerce_xero\Form\StrategyForm",
 *       "delete" = "Drupal\commerce_xero\Form\StrategyDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/commerce/xero/strategy/manage/{commerce_xero_strategy}",
 *     "delete-form" = "/admin/commerce/xero/strategy/manage/{commerce_xero_strategy}/delete"
 *   },
 *   config_prefix = "strategy"
 * )
 */
class CommerceXeroStrategy extends ConfigEntityBase implements CommerceXeroStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public function getEnabledPlugin($plugin_id) {
    $plugins = $this->get('plugins');
    if (!empty($plugins)) {
      foreach ($plugins as $enabled) {
        if ($enabled['name'] === $plugin_id) {
          return $enabled;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginWeight(CommerceXeroProcessorPluginInterface $plugin) {
    $plugins = $this->get('plugins');
    if (!empty($plugins)) {
      foreach ($plugins as $index => $enabled) {
        if ($enabled['name'] === $plugin->getPluginId()) {
          return $index;
        }
      }
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Calculates plugin dependencies.
    $plugins = $this->get('plugins');
    if ($plugins !== NULL) {
      foreach ($plugins as $index => $info) {
        $configuration = ['settings' => $info['settings']];
        $plugin = \Drupal::service('commerce_xero_processor.manager')
          ->createInstance($info['name'], $configuration);
        if ($plugin instanceof DependentPluginInterface) {
          $this->addDependencies($plugin->calculateDependencies());
        }
      }
    }

    // Calculates data type plugin dependencies.
    $type = $this->get('xero_type');
    /** @var \Drupal\commerce_xero\CommerceXeroDataTypePluginInterface $type_plugin */
    $type_plugin = \Drupal::service('commerce_xero_data_type.manager')
      ->createInstance($type);
    if ($type_plugin instanceof DependentPluginInterface) {
      $this->addDependencies($type_plugin->calculateDependencies());
    }

    // Adds commerce_payment_gateway as a dependency.
    $gateway = $this->get('payment_gateway');
    $this->addDependency('config', 'commerce_payment.commerce_payment_gateway.' . $gateway);

    return $this->dependencies;
  }

}
