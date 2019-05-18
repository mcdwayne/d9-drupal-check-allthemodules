<?php

namespace CleverReach\BusinessLogic\Interfaces;

use CleverReach\BusinessLogic\Entity\ShopAttribute;

/**
 * Interface Attributes
 *
 * @package CleverReach\BusinessLogic\Interfaces
 */
interface Attributes
{

    const CLASS_NAME = __CLASS__;
    
    /**
     * Get attribute from integration with translated params in system language.
     *
     * It should set description, preview_value and default_value based on
     * attribute name.
     *
     * @param string $attributeName Desired attribute name.
     *
     * @return ShopAttribute
     *   Attribute object that matched desired attribute name.
     */
    public function getAttributeByName($attributeName);
}
