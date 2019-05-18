<?php

namespace Drupal\clever_reach\Component\BusinessLogic;

use CleverReach\BusinessLogic\Entity\ShopAttribute;
use CleverReach\BusinessLogic\Interfaces\Attributes;

/**
 * Get attribute from Drupal with translated description in default language.
 */
class AttributesService implements Attributes {

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

    switch ($attributeName) {
      case 'email':
        $attribute->setDescription(t('E-mail address'));
        break;

      case 'firstname':
        $attribute->setDescription(t('Username'));
        break;

      case 'shop':
        $attribute->setDescription(t('Site name'));
        break;

      case 'customernumber':
        $attribute->setDescription(t('UID'));
        break;

      case 'language':
        $attribute->setDescription(t('Preferred language'));
        break;

      case 'newsletter':
        $attribute->setDescription(t('Subscribed to newsletter'));
        break;
    }

    return $attribute;
  }

}
