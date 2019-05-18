<?php

/**
 * @file
 * Contains \Drupal\js_injector\Entity\Rule.
 */

namespace Drupal\js_injector\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\js_injector\RuleInterface;

/**
 * Defines the js_injector rule entity class.
 *
 * @ConfigEntityType(
 *   id = "js_injector_rule",
 *   label = @Translation("Rule"),
 *   controllers = {
 *     "form" = {
 *       "default" = "Drupal\js_injector\RuleFormController",
 *       "add" = "Drupal\js_injector\RuleFormController",
 *       "edit" = "Drupal\js_injector\RuleFormController",
 *       "delete" = "Drupal\js_injector\Form\RuleDeleteForm"
 *     },
 *     "storage" = "Drupal\js_injector\RuleStorage",
 *     "list_builder" = "Drupal\js_injector\RuleListBuilder",
 *   },
 *   config_prefix = "rule",
 *   admin_permission = "administer js_injector",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "label" = "title"
 *   },
 *   links = {
 *     "customize-form" = "js_injector.rule_customize",
 *     "delete-form" = "js_injector.rule_delete",
 *     "edit-form" = "js_injector.rule_edit"
 *   }
 * )
 */
class Rule extends ConfigEntityBase implements RuleInterface {

  /**
   * The machine name of this rule.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID of this rule.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable label of this rule.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of this rule in administrative listings.
   *
   * @var int
   */
  public $weight;

  /**
   * The human-readable description of this rule.
   *
   * @var string
   */
  public $description;

  /**
   * The JavaScript contents of this rule.
   *
   * @var string
   */
  public $js;

  /**
   * The position of the site to be rendered - e.g. header or footer.
   *
   * @var string
   */
  public $position;

  /**
   * Whether or not this JavaScript file will be aggregated.
   *
   * @var bool
   */
  public $preprocess;

  /**
   * Whether or not this JavaScript file is inline.
   *
   * @var bool
   */
  public $inline;

  /**
   * Whether the rule has a white or black list for page visibility.
   *
   * @var bool
   */
  public $page_visibility;

  /**
   * A list of pages to either hide or show the JavaScript.
   *
   * @var string
   */
  public $page_visibility_pages;

  /**
   * Overrides \Drupal\Core\Entity\Entity::uri().
   */
  public function uri() {
    return array(
      'path' => 'admin/config/development/js-injector/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }
}
