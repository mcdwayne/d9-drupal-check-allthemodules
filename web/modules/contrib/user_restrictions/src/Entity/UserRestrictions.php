<?php

namespace Drupal\user_restrictions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines an image style configuration entity.
 *
 * @ConfigEntityType(
 *   id = "user_restrictions",
 *   label = @Translation("User restrictions"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\user_restrictions\Form\UserRestrictionsAddForm",
 *       "edit" = "Drupal\user_restrictions\Form\UserRestrictionsEditForm",
 *       "delete" = "Drupal\user_restrictions\Form\UserRestrictionsDeleteForm",
 *     },
 *     "list_builder" = "Drupal\user_restrictions\UserRestrictionsListBuilder",
 *   },
 *   admin_permission = "administer user restrictions",
 *   config_prefix = "user_restrictions",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/user-restrictions/manage/{user_restriction}",
 *     "delete-form" = "/admin/config/people/user-restrictions/manage/{user_restriction}/delete",
 *     "collection" = "/admin/config/people/user-restrictions",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "pattern",
 *     "access_type",
 *     "rule_type",
 *     "expiry",
 *   }
 * )
 */
class UserRestrictions extends ConfigEntityBase implements UserRestrictionInterface {

  /**
   * Constant for permanent rules. Maximum 32bit timestamp.
   */
  const NO_EXPIRY = 2147483647;

  /**
   * Constant to disallow matching values.
   */
  const BLACKLIST = FALSE;

  /**
   * Constant to allow matching values.
   */
  const WHITELIST = TRUE;

  /**
   * The name of the user restriction.
   *
   * @var string
   */
  protected $name;

  /**
   * The user restriction pattern.
   *
   * @var string
   */
  protected $pattern;

  /**
   * The user restriction label.
   *
   * @var string
   */
  protected $label;

  /**
   * Allow or disallow matching values.
   *
   * One of UserRestrictions::BLACKLIST or UserRestrictions::WHITELIST.
   *
   * @var int
   */
  protected $access_type;

  /**
   * Timestamp of rule expiration.
   *
   * @var int
   */
  protected $expiry;

  /**
   * Type of restriction rule.
   *
   * @var string
   *
   * @see \Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface
   */
  protected $rule_type;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiry() {
    return $this->expiry;
  }

  /**
   * {@inheritdoc}
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessType() {
    return $this->access_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuleType() {
    return $this->rule_type;
  }

}
