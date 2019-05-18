<?php
/**
 * @file
 * Fasttoggle Object List of Values Setting
 */

namespace Drupal\fasttoggle\Plugin\SettingGroup;

use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\fasttoggle\Plugin\SettingGroup\SettingGroupInterface;
use Drupal\fasttoggle\Plugin\SettingObject\AbstractSettingObject;

require_once 'SettingGroupInterface.php';

/**
 * Abstract interface for settings.
 */
abstract class AbstractSettingGroup extends AbstractSettingObject implements SettingGroupInterface {

  /**
   * Retrieve the object type that can be modified by this setting.
   *
   * @return string
   *   The machine name for the object type being modified by this setting.
   */
  public function __get($name) {
    // Simple member
    if (isset($this->{$name})) {
      return $this->{$name};
    }

    // Annotation
    $definition = $this->getPluginDefinition();
    if (isset($definition[$name])) {
      return $definition[$name];
    }

    // Specific getter function
    $functionName = "get_${name}";
    if (is_callable([$this, $functionName])) {
      return $this->{$functionName}();
    }

    // Unmatched
    $trace = debug_backtrace();
    trigger_error(
      'Undefined property via __get(): ' . $name .
      ' in ' . $trace[0]['file'] .
      ' on line ' . $trace[0]['line'],
      E_USER_NOTICE);
    return NULL;
  }

  /**
   * Get an array of sitewide setting form elements for this object type.
   *
   * @param $config
   *   The configuration storage.
   *
   * @return array
   *   Render array for the sitewide settings.
   */
  public static function getSitewideSettingFormElements($config) {
    return [];
  }

  /**
   * Access.
   *
   * @return bool
   *   Whether the user is permitted to modify settings in this group of settings.
   */
  public function mayEditGroup() {
    return FALSE;
  }

}
