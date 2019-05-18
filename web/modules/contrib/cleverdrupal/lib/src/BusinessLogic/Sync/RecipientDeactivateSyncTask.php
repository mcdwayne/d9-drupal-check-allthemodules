<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 * Class RecipientDeactivateSyncTask.
 *
 * @package CleverReach\BusinessLogic\Sync
 *
 * @deprecated
 */
class RecipientDeactivateSyncTask extends RecipientStatusUpdateSyncTask {

  /**
   * Runs task logic.
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   */
  public function execute() {
    $this->getProxy()->deactivateRecipients($this->recipientEmails);
    $this->reportProgress(100);
  }

}
