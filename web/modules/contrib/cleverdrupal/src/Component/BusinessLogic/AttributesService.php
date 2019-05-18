<?php

namespace Drupal\cleverreach\Component\BusinessLogic;

use CleverReach\BusinessLogic\Entity\ShopAttribute;
use CleverReach\BusinessLogic\Interfaces\Attributes;

/**
 * Get attribute from Drupal with translated description in default language.
 */
class AttributesService implements Attributes {
  /**
   * Attribute for export.
   *
   * @var array
   */
  private static $attributes = [
    'email' => 'E-mail address',
    'salutation' => NULL,
    'title' => NULL,
    'firstname' => 'Username',
    'lastname' => NULL,
    'birthday' => NULL,
    'shop' => 'Site name',
    'customernumber' => 'UID',
    'language' => 'Preferred language',
    'street' => NULL,
    'zip' => NULL,
    'city' => NULL,
    'company' => NULL,
    'state' => NULL,
    'country' => NULL,
    'phone' => NULL,
    'newsletter' => 'Subscribed to newsletter',
  ];

  /**
   * Get attribute from shop with translated description in shop language.
   *
   * It should set description, preview_value and default_value based on
   * attribute name.
   *
   * @param string $attributeName
   *   Unique attribute code.
   *
   * @return \CleverReach\BusinessLogic\Entity\ShopAttribute
   *   CleverReach attribute object.
   */
  public function getAttributeByName($attributeName) {
    $attribute = new ShopAttribute();
    $mappedAttribute = $this->getMappedAttribute($attributeName);

    if ($mappedAttribute !== NULL) {
      $attribute->setDescription(t($mappedAttribute));
    }

    return $attribute;
  }

  /**
   * Gets mapped attribute name.
   *
   * @param string $attributeName
   *   Required field code.
   *
   * @return string|null
   *   Mapped value.
   */
  private function getMappedAttribute($attributeName) {
    if (!empty(self::$attributes[$attributeName])) {
      return self::$attributes[$attributeName];
    }

    return NULL;
  }

}
