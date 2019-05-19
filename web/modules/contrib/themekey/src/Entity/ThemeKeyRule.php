<?php

/**
 * @file
 * Contains Drupal\themekey\Entity\ThemeKeyRule.
 */

namespace Drupal\themekey\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\themekey\ThemeKeyRuleInterface;
use Drupal\Component\Utility\String;

/**
 * Defines the ThemeKeyRule entity.
 *
 * @ConfigEntityType(
 *   id = "themekey_rule",
 *   label = @Translation("ThemeKey Rule"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\themekey\Controller\ThemeKeyRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\themekey\Form\ThemeKeyRuleForm",
 *       "edit" = "Drupal\themekey\Form\ThemeKeyRuleForm",
 *       "delete" = "Drupal\themekey\Form\ThemeKeyRuleDeleteForm"
 *     }
 *   },
 *   config_prefix = "themekey_rule",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "themekey_rule.edit",
 *     "delete-form" = "themekey_rule.delete"
 *   }
 * )
 */
class ThemeKeyRule extends ConfigEntityBase implements ThemeKeyRuleInterface {

  /**
   * The ThemeKeyRule ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ThemeKeyRule UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The ThemeKeyRule label.
   *
   * @var string
   */
  public $label;

  /**
   * The ThemeKeyRule property.
   *
   * @var string
   */
  public $property;

  /**
   * The ThemeKeyRule key.
   *
   * @var string
   */
  public $key = NULL; /* optional */

  /**
   * The ThemeKeyRule operator.
   *
   * @var string
   */
  public $operator;

  /**
   * The ThemeKeyRule value.
   *
   * @var string
   */
  public $value;

  /**
   * The ThemeKeyRule theme.
   *
   * @var string
   */
  public $theme;

  /**
   * The ThemeKeyRule comment.
   *
   * @var string
   */
  public $comment = ''; /* optional */

  /**
   * @return string
   */
  public function property() {
    return $this->property;
  }

  /**
   * @return string
   */
  public function key() {
    return $this->key;
  }

  /**
   * @return string
   */
  public function operator() {
    return $this->operator;
  }

  /**
   * @return string
   */
  public function value() {
    return $this->value;
  }

  /**
   * @return string
   */
  public function theme() {
    return $this->theme;
  }

  /**
   * @return string
   */
  public function comment() {
    return $this->comment;
  }

  /**
   * Returns a simple string representation of the rule.
   * TODO
   *
   * @return string
   */
  public function toString() {
    return String::checkPlain(
      $this->property() . ' ' .
      ($this->key() ? : '' ) .
      $this->operator() .
      $this->value() . ' >>> ' .
      $this->theme()
    );
  }
}