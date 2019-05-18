<?php

namespace CleverReach\BusinessLogic\Utility;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 * Class Helper
 *
 * @package CleverReach\BusinessLogic\Utility
 */
class Helper
{
    /**
     * Creates Recipient entity from provided array of parameters.
     *
     * @param array $source Recipient data.
     *
     * @param string $integrationName Name of the integration. Needed for tag detection.
     *
     * @return Recipient Created Recipient object.
     */
    public static function createRecipientEntity($source, $integrationName)
    {
        $attributes = !empty($source['global_attributes']) ? $source['global_attributes'] : array();

        $recipientEntity = new Recipient($source['email']);
        $recipientEntity->setSalutation(self::getValueIfNotEmpty('salutation', $attributes));
        $recipientEntity->setTitle(self::getValueIfNotEmpty('title', $attributes));
        $recipientEntity->setFirstName(self::getValueIfNotEmpty('firstname', $attributes));
        $recipientEntity->setLastName(self::getValueIfNotEmpty('lastname', $attributes));
        $recipientEntity->setStreet(self::getValueIfNotEmpty('street', $attributes));
        $recipientEntity->setZip(self::getValueIfNotEmpty('zip', $attributes));
        $recipientEntity->setCity(self::getValueIfNotEmpty('city', $attributes));
        $recipientEntity->setCompany(self::getValueIfNotEmpty('company', $attributes));
        $recipientEntity->setState(self::getValueIfNotEmpty('state', $attributes));
        $recipientEntity->setCountry(self::getValueIfNotEmpty('country', $attributes));
        $recipientEntity->setPhone(self::getValueIfNotEmpty('phone', $attributes));
        $recipientEntity->setShop(self::getValueIfNotEmpty('shop', $source));
        $recipientEntity->setCustomerNumber(self::getValueIfNotEmpty('customernumber', $attributes));
        $recipientEntity->setLanguage(self::getValueIfNotEmpty('language', $attributes));
        $recipientEntity->setNewsletterSubscription(self::getNewsletterStatus($attributes));
        $recipientEntity->setSource(self::getValueIfNotEmpty('source', $source));
        $recipientEntity->setActive((bool)$source['active']);

        self::setTimestamps($source, $recipientEntity);

        self::setTags($source, $recipientEntity, $integrationName);

        return $recipientEntity;
    }

    /**
     * Gets newsletter status from provided array.
     *
     * @param array $array Recipient data.
     *
     * @return bool True if recipient is subscribed; otherwise, false.
     */
    private static function getNewsletterStatus($array)
    {
        $value = self::getValueIfNotEmpty('newsletter', $array);

        return $value === 'yes';
    }

    /**
     * Sets recipient timestamps.
     *
     * @param array $source Recipient data.
     * @param Recipient $recipientEntity Recipient object.
     */
    private static function setTimestamps($source, $recipientEntity)
    {
        $timeProvider = new TimeProvider();
        if (!empty($source['registered'])) {
            $recipientEntity->setRegistered($timeProvider->getDateTime($source['registered']));
        }

        if (!empty($source['activated'])) {
            $recipientEntity->setActivated($timeProvider->getDateTime($source['activated']));
        }

        if (!empty($source['deactivated'])) {
            $recipientEntity->setDeactivated($timeProvider->getDateTime($source['deactivated']));
        }
    }

    /**
     * Sets recipient timestamps.
     *
     * @param array $source Recipient data.
     * @param Recipient $recipientEntity Recipient object.
     * @param string $integrationName Name of the current integration.
     */
    private static function setTags($source, $recipientEntity, $integrationName)
    {
        $tags = !empty($source['tags']) ? $source['tags'] : array();
        $tagCollection = new TagCollection();
        $specialTags = new SpecialTagCollection();
        foreach ($tags as $tag) {
            if (strpos($tag, $integrationName . '-') === 0) {
                $tag = substr($tag, strlen($integrationName . '-'));
                list($tagType, $tagName) = explode('.', $tag);

                if ($tagType === 'Special') {
                    $specialTags->addTag(SpecialTag::fromString($tagName));
                } else {
                    $tagCollection->addTag(new Tag($tagName, $tagType));
                }
            }
        }

        $recipientEntity->setTags($tagCollection);
        $recipientEntity->setSpecialTags($specialTags);
    }

    /**
     * Gets the value from array for specific key, if exists.
     *
     * @param string $key Array key.
     * @param array $array Source array.
     *
     * @return mixed Value if exists; otherwise, null.
     */
    private static function getValueIfNotEmpty($key, $array)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        return null;
    }
}
