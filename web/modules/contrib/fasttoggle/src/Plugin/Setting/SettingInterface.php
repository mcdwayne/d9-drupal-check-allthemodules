<?php
/**
 * @file
 * Provides Drupal\fasttoggle\SettingInterface.
 */

namespace Drupal\fasttoggle\Plugin\Setting;

use Drupal\fasttoggle\Plugin\SettingGroup\SettingGroupInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\field\Entity\FieldConfig;

/**
 * Interface for settings. Plugin strings are used for quick
 * filtering without the need to instantiate the class.
 *
 * A plugin definition should have:
 * - id: Unique ID for the plugin in the settings namespace
 * - object: The object type modified by this setting (matches
 * \Drupal\fasttoggle\Plugin\SettingObject)
 * - name: A short, human readable name for this setting (will be
 * included in sentences).
 * - description: Help text for site and group setting forms.
 * - group: The group of settings within forms.
 * - weight: The ordering of the setting within its group.
 * - default_access: Whether to allow fast toggling of this setting
 * by default.
 *
 * Plus either:
 * - labels: An array of FASTTOGGLE_LABEL_ACTION and FASTTOGGLE_LABEL_STATUS,
 * each itself containing an array of key -> value pairs of states
 * for the setting. Keys are values to store in the setting and values
 * are textual descriptions, to be passed to t().
 *
 * OR for a single setting that can have multiple instances (roles):
 * - label_template: Like labels above except that the content may
 * contain %s once. %s will be replaced with the human readable name
 * of the setting value (eg: role name).
 * - description template: A description that may also have the %s
 * placeholder.
 */
interface SettingInterface extends SettingGroupInterface {

  /**
   * Retrieve the current value of the setting.
   *
   * @param string $instance
   *    The name of the particular attribute being toggled.
   *
   * @return string
   *   The current key matching getHumanReadableValueList / getValueList.
   */
  function get_value($instance);

  /**
   * Modify the setting.
   *
   * @param string instance
   *   The instance (role name / value index ... ) to modify.
   * @param mixed newValue
   *   The new value to save
   *
   * @return \Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface
   *   The related object, so you can chain a call to its the save method.
   */
  function set_value($instance, $newValue);

  /**
   * Move to the next setting value.
   *
   * @return \Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface
   *   The related object, so you can chain a call to its the save method.
   */
  public function nextValue($instance);

  /**
   * Move to the previous setting value and save it.
   *
   * (Allows some widget to implement forward and back buttons if desired).
   *
   * @return \Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface
   *   The related object, so you can chain a call to its the save method.
   */
  public function previousValue($instance);

  /**
   * Get a plain text list of human readable labels for the setting, in the
   * order used.
   *
   * This allows human readable labels to be sorted in non-alphabetical order.
   * Note that the widget object may use this or an attribute of the value
   * itself to render an icon, an ajax link or something else.
   *
   * @return array
   *   An array of human readable values, in the order they will appear when
   *   stepping through them.
   */
  public function getHumanReadableValueList();

  /**
   * Get a list of actual values for the setting, in the order used.
   *
   * Keys should match those returned for the list of human readable labels.
   *
   * @return array
   *   An array of the actual values for the field, with keys matching those
   *   returned by getHumanReadableValueList.
   */
  public function getValueList();

  /**
   * Return the sitewide form element for this setting.
   *
   * @return array
   *   Form element for this setting.
   */
  public function settingForm($config, $prefix);

  /**
   * Write access control check for the object as a whole.
   *
   * @return bool
   *   Whether the user is permitted to modify settings on this object instance.
   */
  public function mayEdit();

  /**
   * Write access control check for the particular setting.
   *
   * @return bool
   *   Whether the user is permitted to modify this particular setting.
   */
  public function mayEditSetting();

  /**
   * Return whether this setting matches the provided field definition.
   *
   * @param $definition
   *   The field definition for which a match is being sought.
   *
   * @return boolean
   *   Whether this plugin handles the definition.
   */
  public static function matches($definition);

  /**
   * Get the markup we modify.
   *
   * @param \Drupal\Core\Field\FieldItemList $items
   *   The items to be displayed.
   * @param array $config
   *   The configuration used to generate the original link.
   */
  public function formatter(FieldItemList $items, $config);

}
