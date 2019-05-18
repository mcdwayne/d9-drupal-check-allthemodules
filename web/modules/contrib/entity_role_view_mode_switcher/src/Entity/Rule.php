<?php

namespace Drupal\entity_role_view_mode_switcher\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the View Mode Switcher Rule entity.
 *
 * @ConfigEntityType(
 *   id = "rule",
 *   label = @Translation("View Mode Switcher Rule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_role_view_mode_switcher\RuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_role_view_mode_switcher\Form\RuleForm",
 *       "edit" = "Drupal\entity_role_view_mode_switcher\Form\RuleForm",
 *       "delete" = "Drupal\entity_role_view_mode_switcher\Form\RuleDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_role_view_mode_switcher\RuleHtmlRouteProvider",
 *     },
 *    "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *    "permission_provider" = "Drupal\Core\Entity\EntityAccessControlHandler"
 *   },
 *   config_prefix = "rule",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/entity_role_view_mode_switcher_rule/{rule}",
 *     "add-form" = "/admin/structure/entity_role_view_mode_switcher_rule/add",
 *     "edit-form" = "/admin/structure/entity_role_view_mode_switcher_rule/{rule}/edit",
 *     "delete-form" = "/admin/structure/entity_role_view_mode_switcher_rule/{rule}/delete",
 *     "collection" = "/admin/structure/entity_role_view_mode_switcher_rule"
 *   }
 * )
 */
class Rule extends ConfigEntityBase implements RuleInterface {

  /**
   * The View Mode Switcher Rule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The View Mode Switcher Rule label.
   *
   * @var string
   */
  protected $label;

  /**
   * Conditions. This is an array of arrays.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * {@inheritdoc}
   */
  public function setConditionsFromArray(array $conditionsArray) {
    // Clear existing conditions.
    $this->conditions = [];
    foreach ($conditionsArray as $condition) {
      $this->conditions[] = [
        'negate' => $condition['negate'],
        'role_id' => $condition['role_id'],
        'original_view_mode_id' => $condition['original_view_mode_id'],
        'new_view_mode_id' => $condition['new_view_mode_id'],
      ];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    return !empty($this->conditions) ? $this->conditions : [];
  }

}
