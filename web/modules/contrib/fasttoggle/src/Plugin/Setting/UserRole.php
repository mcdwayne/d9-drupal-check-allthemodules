<?php
/**
 * @file
 * Fasttoggle User Status
 */

namespace Drupal\fasttoggle\Plugin\Setting;

require_once __DIR__ . '/../../../fasttoggle.inc';

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\Role;
use Drupal\Core\Field\FieldItemList;
use Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\fasttoggle\Plugin\SettingGroup\UserRoles;
use Drupal\fasttoggle\Plugin\Setting\SettingTrait;

/**
 * Abstract interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * No attributes member in the annotation - calculated value
 *
 * @Plugin(
 *   id = "user_roles",
 *   entityType = "user",
 *   name = "role",
 *   description = "Roles that may be granted to this user",
 *   group = "user_roles",
 *   weight = 100,
 *   default = false,
 *   base_formatter = "Drupal\fasttoggle\Plugin\Field\FieldFormatter\BooleanFormatter",
 *   labels = {
 *     FASTTOGGLE_LABEL_ACTION = {
 *       0 = "Grant '%s'",
 *       1 = "Revoke '%s'",
 *     },
 *     FASTTOGGLE_LABEL_STATUS = {
 *       0 = "Granted '%s'",
 *       1 = "'%s' not granted",
 *     },
 *   },
 *   description_template = "Toggle '@rolename' role",
 *   attributeWeight = 0,
 * )
 */
class UserRole extends UserRoles implements SettingInterface {

  use SettingTrait;

  /**
   * @var attributes - Array of attribute name => description pairs.
   */
  private $attributes;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->trait_constructor($configuration, $plugin_id, $plugin_definition);

    $role_objects = Role::loadMultiple();
    unset($role_objects['anonymous']);
    unset($role_objects['authenticated']);
    $this->attributes = array_combine(array_keys($role_objects), array_map(function($a){ return $a->label();}, $role_objects));
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Retrieve the list of roles that can be modified by this setting.
   *
   * @return array
   *   An array containing strings used to identify the attributes to get/set
   *   and the settings form.
   */
  protected function get_attributes() {
    return $this->attributes;
  }

  /**
   * Return the description for the attribute being displayed.
   *
   * @return TranslatableMarkup?
   *   The attribute description.
   */
  function attributeDescription($attributeName) {
    // Strip off "user_" from the front.
    $attribute = $this->attributes[substr($attributeName, 11)];
    $definition = $this->getPluginDefinition();
    return t($definition['description_template'], ['@rolename' => $attribute]);
  }

  /**
   * Access control function.
   *
   * @param $user
   *   The user against which to check (un)publish permission.
   *
   * @return boolean
   *   Whether the user is allowed to (un)publish the user.
   */
  public function mayEditSetting() {
    $user = \Drupal::currentUser();
    return AccessResult::allowedIfHasPermission($user, "override user blocked option");
  }

  /**
   * Return the sitewide form element for this setting.
   *
   * @return array
   *   Form element for this setting.
   */
  public function settingForm($config, $attribute) {
    $sitewide_access = $config->get($attribute);
    if (is_null($sitewide_access)) {
      $sitewide_access = $this->default;
    }

    $fieldArray = [
      '#type' => 'checkbox',
      '#default_value' => $sitewide_access,
      '#title' => $this->attributeDescription($attribute),
      '#weight' => $this->attributeWeight,
    ];

    return $fieldArray;
  }

  /**
   * Return whether this setting matches the provided field definition.
   *
   * @param $definition
   *   The field definition for which a match is being sought.
   *
   * @return boolean
   *   Whether this plugin handles the definition.
   */
  public static function matches($definition) {
    $has_get = is_callable(array($definition, 'get'));
    $entity = $has_get ? $definition->get('entity_type') : $definition->getProvider();
    return ($entity == 'user' && $definition->getName() == 'role');
  }

}
