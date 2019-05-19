<?php

/**
 * @file
 * Contains \Drupal\user_badges\Entity\BadgeType.
 */

namespace Drupal\user_badges\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\user_badges\BadgeTypeInterface;

/**
 * Defines the Badge type entity.
 *
 * @ConfigEntityType(
 *   id = "badge_type",
 *   label = @Translation("Badge type"),
 *   handlers = {
 *     "list_builder" = "Drupal\user_badges\BadgeTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\user_badges\Form\BadgeTypeForm",
 *       "edit" = "Drupal\user_badges\Form\BadgeTypeForm",
 *       "delete" = "Drupal\user_badges\Form\BadgeTypeDeleteForm"
 *     },
 *   },
 *   config_prefix = "badge_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "badge",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/badge_type/{badge_type}",
 *     "add-form" = "/admin/structure/badge_type/add",
 *     "edit-form" = "/admin/structure/badge_type/{badge_type}/edit",
 *     "delete-form" = "/admin/structure/badge_type/{badge_type}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "automatic",
 *   }
 * )
 */
class BadgeType extends ConfigEntityBase implements BadgeTypeInterface {
  /**
   * The Badge type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Badge type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Automatic Badge Assignment.
   * It is TRUE if badge assignment is automatic i.e Role badges. If user gets
   * role, it's relevant role badge is assigned.
   *
   * @var string
   */
  protected $automatic;

  /**
   * {@inheritdoc}
   */
  public function getAutomaticBadgeStatus() {
    return $this->automatic;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutomaticBadgeStatus($status) {
    $this->automatic = $status;
  }

}
