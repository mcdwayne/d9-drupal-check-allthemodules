<?php

namespace CleverReach\BusinessLogic\Entity;

/**
 * Class ShopAttribute
 *
 * @package CleverReach\BusinessLogic\Entity
 */
class ShopAttribute
{
    /**
     * Attribute description.
     *
     * @var string
     */
    private $description = '';
    /**
     * Attribute preview value.
     *
     * @var string
     */
    private $previewValue = '';
    /**
     * Attribute default value.
     *
     * @var string
     */
    private $defaultValue = '';

    /**
     * Set attribute description.
     *
     * @param string $description Attribute description.
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Set attribute preview value.
     *
     * @param string $previewValue Attribute preview value.
     */
    public function setPreviewValue($previewValue)
    {
        $this->previewValue = $previewValue;
    }

    /**
     * Set attribute default value.
     *
     * @param string $defaultValue Attribute default value.
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Get attribute description.
     *
     * @return string
     *   If not set returns empty string, otherwise set attribute description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get attribute preview value.
     *
     * @return string
     *   If not set returns empty string, otherwise set preview value.
     */
    public function getPreviewValue()
    {
        return $this->previewValue;
    }

    /**
     * Get attribute default value.
     *
     * @return string
     *   If not set returns empty string, otherwise set default value.
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
