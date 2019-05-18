<?php
/**
 * @file
 * Provides Drupal\fasttoggle\ObjectInterface.
 */

namespace Drupal\fasttoggle\Plugin\SettingObject;

/**
 * An interface for objects that have toggleable attributes.
 *
 * The object class should have a Plugin annotation with the following
 * attributes:
 * - title: Heading for settings forms.
 * - description: Help text for settings forms.
 * - machine_name: A name for the object type - will be used to build
 * classnames, selectors, form elements and so on. Lowercase letters and
 * underscores.
 *
 * Abstract class provides a private $object member and default implementations
 * for:
 * - setObject: Save a passed object into the $object protected member.
 * - getSubType: Subtype of the object (eg node type) being empty (no subtype)
 */
interface SettingObjectInterface {

  /**
   * Set an instance of the object.
   */
  public function setObject($object);

  /**
   * Object ID.
   *
   * @return integer
   *   The unique ID of this instance of the object.
   */
  public function get_id();

  /**
   * Get the node / user / ...
   *
   * @return object
   *   The object being modified.
   */
  public function get_object();

  /**
   * Object title.
   *
   * @return integer
   *   The title of the object for display.
   */
  public function get_title();

  /**
   * Save function. Update the entity in the database.
   *
   * @return bool
   *   Whether the object was successfully saved.
   */
  public function save();

  /**
   * Object subtype machine name.
   *
   * @return string
   *   A subtype (if any) of the object (eg node type).
   */
  public function get_type();

  /**
   * Write access control check for the object as a whole.
   *
   * @return bool
   *   Whether the user is permitted to modify settings on this object instance.
   */
  public function mayEditEntity();

  /**
   * Matches an object?
   *
   * @param $object
   *   The object to be checked.
   *
   * @return boolean
   *   Whether this class handles that object.
   */

  public function objectMatches($object);

  /**
   * Get an array of sitewide setting form elements for this object type.
   *
   * @param $config
   *   The configuration storage.
   *
   * @return array
   *   Render array for the sitewide settings.
   */
  public static function getSitewideSettingFormElements($config);

}
