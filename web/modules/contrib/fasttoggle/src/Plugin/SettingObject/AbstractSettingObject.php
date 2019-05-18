<?php
/**
 * @file
 * Abstract Fasttoggle Object
 */

namespace Drupal\fasttoggle\Plugin\SettingObject;

use Drupal\Core\Plugin\PluginBase;
use Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface;

require_once __DIR__ . "/SettingObjectInterface.php";

/**
 * Abstract class for an object on which Fasttoggle can modify settings.
 */
abstract class AbstractSettingObject extends PluginBase implements SettingObjectInterface {

  /**
   * @var object $object
   *   The object being managed.
   * */
  protected $object;

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
   * Set an instance of the object.
   */
  public function setObject($object) {
    $this->object = $object;
  }

  /**
   * Get the object instance.
   *
   * @return object
   *   The instance of the object, if any.
   */
  public function get_object() {
    return $this->object;
  }

  /**
   * Object ID.
   *
   * @return integer
   *   The unique ID of this instance of the object.
   */
  abstract public function get_id();

  /**
   * Object title.
   *
   * @return integer
   *   The title of the object for display.
   */
  abstract public function get_title();

  /**
   * Save function. Update the entity in the database.
   *
   * @return bool
   *   Whether the object was successfully saved.
   */
  abstract public function save();

  /**
   * Object subtype machine name.
   *
   * @return string
   *   A subtype (if any) of the object (eg node type).
   */
  public function get_type() {
    return '';
  }

  /**
   * Access.
   *
   * @return bool
   *   Whether the user is permitted to modify settings on this object instance.
   */
  public function mayEditEntity() {
    return FALSE;
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

}
