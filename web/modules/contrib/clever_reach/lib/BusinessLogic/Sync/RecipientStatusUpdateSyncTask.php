<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 * Class RecipientStatusUpdateSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
abstract class RecipientStatusUpdateSyncTask extends BaseSyncTask
{
    /**
     * Array of recipient emails that should be updated.
     *
     * @var array
     */
    public $recipientEmails;

    /**
     * RecipientStatusUpdateSyncTask constructor.
     *
     * @param array $recipientEmails Array of recipient emails that should be updated.
     */
    public function __construct(array $recipientEmails)
    {
        $this->recipientEmails = $recipientEmails;
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->recipientEmails);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->recipientEmails = unserialize($serialized);
    }
}
