<?php

namespace CleverReach\BusinessLogic\Interfaces;

/**
 * Interface Recipients
 *
 * @package CleverReach\BusinessLogic\Interfaces
 */
interface Recipients
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets all tags as a collection.
     *
     * @return \CleverReach\BusinessLogic\Entity\TagCollection
     *   Collection of integration tags.
     */
    public function getAllTags();

    /**
     * Gets all special tags as a collection.
     *
     * @return \CleverReach\BusinessLogic\Entity\SpecialTagCollection
     *   Collection of integration supported special tags.
     */
    public function getAllSpecialTags();

    /**
     * Gets all recipients for passed batch IDs with tags.
     *
     * SPECIAL ATTENTION should be pointed towards tags. They should be set
     * as TagCollection on Recipient instance.
     *
     * @param array $batchRecipientIds Array of recipient IDs that should be fetched.
     * @param bool $includeOrders If includeOrders flag is set to true, orders should
     *     also be returned with other recipient data, otherwise not.
     *
     * @return \CleverReach\BusinessLogic\Entity\Recipient[]
     *  Objects based on passed IDs.
     *
     * @see \CleverReach\BusinessLogic\Entity\Recipient
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     *   When recipients can't be fetched.
     */
    public function getRecipientsWithTags(array $batchRecipientIds, $includeOrders);

    /**
     * Gets all recipients IDs from source system.
     *
     * @return string[]
     *   Array of recipient IDs.
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     *   When recipients can't be fetched.
     */
    public function getAllRecipientsIds();

    /**
     * Informs service about completed synchronization of provided recipients IDs.
     *
     * @param array $recipientIds
     *   Array of recipient IDs that are successfully synchronized.
     */
    public function recipientSyncCompleted(array $recipientIds);
}
