<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Entity\ShopAttribute;
use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\Infrastructure\ServiceRegister;

/**
 * Class AttributesSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
class AttributesSyncTask extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 5;
    /**
     * Array of global attribute IDs on CleverReach.
     *
     * @var array
     */
    private $globalAttributesIdsFromCR;

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->globalAttributesIdsFromCR);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->globalAttributesIdsFromCR = unserialize($serialized);
    }

    /**
     * Runs task execution.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $globalAttributes = $this->getGlobalAttributesIdsFromCleverReach();
        // After retrieving all global attributes set initial progress
        $progressPercent = self::INITIAL_PROGRESS_PERCENT;
        $this->reportProgress($progressPercent);

        $attributesToSend = $this->getAllAttributes();

        // Calculate progress step after setting initially progress
        $totalAttributes = count($attributesToSend);
        $progressStep = (100 - self::INITIAL_PROGRESS_PERCENT) / $totalAttributes;
        $i = 0;
        foreach ($attributesToSend as $attribute) {
            $i++;
            if (isset($globalAttributes[$attribute['name']])) {
                $attributeIdOnCR = $globalAttributes[$attribute['name']];
                $this->getProxy()->updateGlobalAttribute($attributeIdOnCR, $attribute);
            } else {
                $this->getProxy()->createGlobalAttribute($attribute);
            }

            $progressPercent += $progressStep;
            if ($i === $totalAttributes) {
                $this->reportProgress(100);
            } else {
                $this->reportProgress($progressPercent);
            }
        }
    }

    /**
     * Get global attributes IDs from CleverReach.
     *
     * @return array
     *   Array of global attribute IDs on CleverReach.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function getGlobalAttributesIdsFromCleverReach()
    {
        if (empty($this->globalAttributesIdsFromCR)) {
            $this->globalAttributesIdsFromCR = $this->getProxy()->getAllGlobalAttributes();
        }

        return $this->globalAttributesIdsFromCR;
    }

    /**
     * Return all global attributes formatted for sending to CleverReach.
     *
     * Example:
     * [
     *   [
     *     'name' => 'email',
     *     'type' => 'text',
     *   ],
     *   [
     *     'name' => 'birthday',
     *     'type' => 'date',
     *   ]
     * ]
     *
     * @return array
     *   Array of global attributes supported by integration.
     */
    private function getAllAttributes()
    {
        /** @var Attributes $attributesService */
        $attributesService = ServiceRegister::getService(Attributes::CLASS_NAME);
        $attributes = array(
            array(
                'name' => 'email',
                'type' => 'text',
            ),
            array(
                'name' => 'salutation',
                'type' => 'text',
            ),
            array(
                'name' => 'title',
                'type' => 'text',
            ),
            array(
                'name' => 'firstname',
                'type' => 'text',
            ),
            array(
                'name' => 'lastname',
                'type' => 'text',
            ),
            array(
                'name' => 'street',
                'type' => 'text',
            ),
            array(
                'name' => 'zip',
                'type' => 'text',
            ),
            array(
                'name' => 'city',
                'type' => 'text',
            ),
            array(
                'name' => 'company',
                'type' => 'text',
            ),
            array(
                'name' => 'state',
                'type' => 'text',
            ),
            array(
                'name' => 'country',
                'type' => 'text',
            ),
            array(
                'name' => 'birthday',
                'type' => 'date',
            ),
            array(
                'name' => 'phone',
                'type' => 'text',
            ),
            array(
                'name' => 'shop',
                'type' => 'text',
            ),
            array(
                'name' => 'customernumber',
                'type' => 'text',
            ),
            array(
                'name' => 'language',
                'type' => 'text',
            ),
            array(
                'name' => 'newsletter',
                'type' => 'text',
            ),
        );

        /** @var ShopAttribute $attribute */
        foreach ($attributes as &$attribute) {
            $shopAttribute = $attributesService->getAttributeByName($attribute['name']);
            $attribute['description'] = $shopAttribute->getDescription();
            $attribute['preview_value'] = $shopAttribute->getPreviewValue();
            $attribute['default_value'] = $shopAttribute->getDefaultValue();
        }

        return $attributes;
    }
}
