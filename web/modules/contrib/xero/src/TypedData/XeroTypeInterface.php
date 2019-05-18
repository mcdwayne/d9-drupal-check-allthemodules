<?php

namespace Drupal\xero\TypedData;

use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Interface to describe methods useful for Xero API integration that Xero
 * complex data types in Drupal must adhere (i.e. Contacts, but not LineItems).
 */
interface XeroTypeInterface extends TypedDataInterface {

  /**
   * Get the GUID property name.
   *
   * @param string
   *   The name of the Xero GUID field for this type.
   *
   * @deprecated Use XeroTypeInterface::getXeroProperty instead.
   */
  public function getGUIDName();

  /**
   * Get the Plural property name.
   *
   * @param string
   *   The plural for this type.
   *
   * @deprecated Use XeroTypeInterface::getXeroProperty instead.
   */
  public function getPluralName();

  /**
   * Get the Label property.
   *
   * @param string
   *   The Label property for this type, if any.
   *
   * @deprecated Use XeroTypeInterface::getXeroProperty instead.
   */
  public function getLabelName();

  /**
   * Gets one of the xero static properties by name.
   *
   * @param string $name
   *   The name of a static property on the class. This should be one of:
   *     - guid_name
   *     - xero_name
   *     - plural_name
   *     - label_name
   *
   * @return string|NULL
   */
  public static function getXeroProperty($name);

  /**
   * Render the typed data into a render element.
   *
   * @return array
   *   A render array.
   */
  public function view();
}
