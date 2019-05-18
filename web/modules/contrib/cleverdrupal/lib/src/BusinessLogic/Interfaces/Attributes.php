<?php

namespace CleverReach\BusinessLogic\Interfaces;

/**
 *
 */
interface Attributes {

  const CLASS_NAME = __CLASS__;

  /**
   * Get attribute from shop with translated description in shop language
   * It should set description, preview_value and default_value based on attribute name .
   *
   * @param string $attributeName
   *
   * @return \CleverReach\BusinessLogic\Entity\ShopAttribute
   */
  public function getAttributeByName($attributeName);

}
