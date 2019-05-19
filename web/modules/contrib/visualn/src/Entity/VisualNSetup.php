<?php

namespace Drupal\visualn\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VisualN Setup entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_setup",
 *   label = @Translation("VisualN Setup"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn\VisualNSetupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn\Form\VisualNSetupForm",
 *       "edit" = "Drupal\visualn\Form\VisualNSetupForm",
 *       "delete" = "Drupal\visualn\Form\VisualNSetupDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn\VisualNSetupHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_setup",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "baker_id" = "baker_id",
 *     "baker_config" = "baker_config"
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/setups/manage/{visualn_setup}",
 *     "add-form" = "/admin/visualn/setups/add",
 *     "edit-form" = "/admin/visualn/setups/manage/{visualn_setup}/edit",
 *     "delete-form" = "/admin/visualn/setups/manage/{visualn_setup}/delete",
 *     "collection" = "/admin/visualn/setups"
 *   }
 * )
 */
class VisualNSetup extends ConfigEntityBase implements VisualNSetupInterface {

  /**
   * The VisualN Setup ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Setup label.
   *
   * @var string
   */
  protected $label;

  /**
   * The VisualN setup baker ID.
   *
   * @var string
   */
  protected $baker_id;

  /**
   * The VisualN setup baker config.
   *
   * @var array
   */
  protected $baker_config = [];

  /**
   * The VisualN setup specific baker plugin.
   *
   * @var \Drupal\visualn\Core\SetupBakerInterface
   */
  protected $baker_plugin;

  /**
   * {@inheritdoc}
   */
  public function getBakerId() {
    return $this->baker_id ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupBakerPlugin() {
    if (!isset($this->baker_plugin)) {
      $baker_id = $this->getBakerId();
      if (!empty($baker_id)) {
        $baker_config = [];
        $baker_config = $this->getBakerConfig() + $baker_config;
        // @todo: load manager at object instantiation
        $this->baker_plugin = \Drupal::service('plugin.manager.visualn.setup_baker')->createInstance($baker_id, $baker_config);
      }
    }

    return $this->baker_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getBakerConfig() {
    return $this->baker_config;
  }

  /**
   * {@inheritdoc}
   */
  public function setBakerConfig($baker_config) {
    $this->baker = $baker_config;
    return $this;
  }

}
