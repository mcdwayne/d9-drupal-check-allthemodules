<?php

/**
 * @file
 * Contains \Drupal\packery\Entity\PackeryGroup.
 */

namespace Drupal\packery\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;


/**
 * Defines the Group entity.
 *
 * The group entity stores information about a settings group.
 *
 * @ConfigEntityType(
 *   id = "packery_group",
 *   label = @Translation("Packery Group"),
 *   module = "packery",
 *   config_prefix = "group",
 *   admin_permission = "administer packery settings",
 *   handlers = {
 *     "storage" = "Drupal\packery\PackeryGroupStorage",
 *     "form" = {
 *       "default" = "Drupal\packery\Form\PackeryGroupForm",
 *       "delete" = "Drupal\packery\Form\PackeryGroupDeleteForm"
 *     },
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/group/manage/{packery_group}",
 *     "delete-form" = "/admin/config/group/manage/{packery_group}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "settings"
 *   }
 * )
 */
class PackeryGroup extends ConfigEntityBase implements PackeryGroupInterface {

  /**
   * The group machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The group human readable name.
   *
   * @var string
   */
  protected $label;

  /**
   * The array of settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }
}
