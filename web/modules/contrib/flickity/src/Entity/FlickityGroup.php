<?php

/**
 * @file
 * Contains \Drupal\flickity\Entity\FlickityGroup.
 */

namespace Drupal\flickity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Flickity Group entity.
 *
 * The group entity stores information about a settings group.
 *
 * @ConfigEntityType(
 *   id = "flickity_group",
 *   label = @Translation("Flickity Group"),
 *   module = "flickity",
 *   config_prefix = "group",
 *   admin_permission = "administer flickity settings",
 *   handlers = {
 *     "storage" = "Drupal\flickity\FlickityGroupStorage",
 *     "form" = {
 *       "default" = "Drupal\flickity\Form\FlickityGroupForm",
 *       "delete" = "Drupal\flickity\Form\FlickityGroupDeleteForm"
 *     },
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/group/manage/{flickity_group}",
 *     "delete-form" = "/admin/config/group/manage/{flickity_group}/delete"
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
class FlickityGroup extends ConfigEntityBase implements FlickityGroupInterface {

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
   * The group of settings.
   *
   * @var boolean
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

}
