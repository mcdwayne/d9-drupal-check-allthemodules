<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\Entity\ErpalBudgetType.
 */

namespace Drupal\erpal_budget\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\erpal_budget\ErpalBudgetTypeInterface;

/**
 * Defines the Erpal budget type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "erpal_budget_type",
 *   label = @Translation("Erpal Budget type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\erpal_budget\Form\ErpalBudgetTypeForm",
 *       "edit" = "Drupal\erpal_budget\Form\ErpalBudgetTypeForm",
 *       "delete" = "Drupal\erpal_budget\Form\ErpalBudgetTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\erpal_budget\ErpalBudgetTypeListBuilder",
 *   },
 *   admin_permission = "administer site configuration",
 *   config_prefix = "type",
 *   bundle_of = "erpal_budget",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "entity.erpal_budget_type.edit_form",
 *     "delete-form" = "entity.erpal_budget_type.delete_form"
 *   }
 * )
 */
class ErpalBudgetType extends ConfigEntityBundleBase implements ErpalBudgetTypeInterface {
  /**
   * The ID of the erpal budget type.
   *
   * @var string
   */
  public $id;

  /**
   * The erpal budget type name.
   *
   * @var string
   */
  public $name;

  /**
   * The erpal budget type name.
   *
   * @var string
   */
  public $description;

  /**
   * The erpal budget type unit type.
   *
   * @var string
   */
  public $unit_type;
}
