<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 * Class RecipientDeactivateNewsletterStatusSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 * @deprecated
 */
class RecipientDeactivateNewsletterStatusSyncTask extends RecipientStatusUpdateSyncTask
{
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
        $this->getProxy()->updateNewsletterStatus($this->recipientEmails);
        $this->reportProgress(100);
    }
}
