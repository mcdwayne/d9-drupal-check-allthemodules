<?php

namespace Drupal\modal_config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Shortcut set configuration entity.
 *
 * @ConfigEntityType(
 *   id = "modal_config",
 *   label = @Translation("Modal configuration"),
 *   handlers = {
 *     "storage" = "Drupal\modal_config\ModalConfigStorage",
 *     "access" = "Drupal\modal_config\ModalConfigAccessControlHandler",
 *     "list_builder" = "Drupal\modal_config\ModalConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\modal_config\ModalConfigForm",
 *       "add" = "Drupal\modal_config\ModalConfigForm",
 *       "edit" = "Drupal\modal_config\ModalConfigForm",
 *       "delete" = "Drupal\modal_config\Form\ModalConfigDeleteForm"
 *     }
 *   },
 *   config_prefix = "form",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "config_key" = "config_key",
 *     "config_value" = "config_value"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/user-interface/modal-config/manage/{modal_config}/delete",
 *     "edit-form" = "/admin/config/user-interface/modal-config/manage/{modal_config}",
 *     "collection" = "/admin/config/user-interface/modal-config",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "config_key",
 *     "config_value",
 *   }
 * )
 */
class ModalConfig extends ConfigEntityBase implements ConfigEntityInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  protected $label;


  /**
   * Provides configuration key of modal config.
   */
  public function configKey() {
    return $this->get('config_key');
  }

  /**
   * Provides configuration value of modal config.
   */
  public function configValue() {
    return $this->get('config_value');
  }

}
