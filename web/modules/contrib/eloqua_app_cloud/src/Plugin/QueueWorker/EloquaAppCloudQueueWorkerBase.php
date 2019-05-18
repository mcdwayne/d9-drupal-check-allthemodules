<?php

namespace Drupal\eloqua_app_cloud\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\eloqua_app_cloud\Exception\EloquaAppCloudQueueException;
use Exception;
use Psr\Log\LoggerInterface;


abstract class EloquaAppCloudQueueWorkerBase extends QueueWorkerBase implements QueueWorkerInterface, ContainerFactoryPluginInterface {


  /**
   * {@inheritdoc}
   */
  public function processItem($queueItem) {

  }

  /**
   * Wraps try/catch and retry attempts around Elomentary's $bulkApi->upload()
   * function.
   *
   * @param {Object} $bulkApi
   *    Elomentary bulkApi object reference.
   *
   * @param {Array} $contactsToBeStaged
   *    Reference to the $contacts to be staged array.
   *
   * @param {Logger} $logger
   *    Logger object for logging routine info / errors.
   *
   * @throws \Drupal\eloqua_app_cloud\Exception\EloquaAppCloudQueueException
   */
  protected function tryBulkApiUpload($bulkApi, $contactsToBeStaged, LoggerInterface $logger) {
    $retries = 1;
    $maxRetries = 10;

    try {
      // Upload content to Eloqua staging area.
      $bulkApi->upload($contactsToBeStaged);
    } catch (Exception $e) {

      $msg = "Upload Failed! Caught Exception: " . $e->getMessage();
      $logger->error($msg);

      // Retry for $maxRetries attempts.
      for ($retries; $retries <= $maxRetries; $retries++) {
        try {

          // Upload content to Eloqua staging area.
          $bulkApi->upload($contactsToBeStaged);
          // Uploaded contacts successfully.
          return;
        } catch (Exception $e) {
          $msg = "Re-upload attempt #$retries Failed! Caught Exception: " . $e->getMessage();
          $logger->error($msg);
        }
      }

      $msg = "Upload failed after multiple retries.";
      $msg .= print_r($bulkApi->log(), TRUE);
      $msg .= "Caught Exception: " . $e->getMessage();
      $logger->error($msg);
      // Throw an exception and let core re-queue.
      throw new EloquaAppCloudQueueException("Eloqua bulk upload failed after multiple retries.");
    }
  }

  /**
   * Wraps try/catch and retry attempts around Elomentary's $bulkApi->sync()
   * function.
   *
   * @param {Object} $bulkApi
   *    Elomentary bulkApi object reference.
   *
   * @param {Logger} $logger
   *    Logger object for logging routine info / errors.
   *
   * @return {String} $uri
   *    The Sync endpoint URI used to access Eloqua Sync data.
   *
   * @throws \Drupal\eloqua_app_cloud\Exception\EloquaAppCloudQueueException
   */
  protected function tryBulkApiSync($bulkApi, LoggerInterface $logger) {
    $retries = 1;
    // In case of API failures, up to 5 retry attempts.
    $maxRetries = 5;

    try {
      // Sync uploaded contacts in staging with Eloqua.
      $bulkApi->sync();
      $currentStatus = $this->getBulkApiStatus($bulkApi);

      // $bulkApi->status() when passed true,
      // will block until the upload succeeds or fails.
      if ($currentStatus === 'success' || $currentStatus === 'warning') {
        $msg = "Status Returned: " . $currentStatus;
        $logger->info($msg);
        $msg = "Sync completed successfully!";
        $logger->info($msg);
      }

      $syncResponse = $bulkApi->getResponse('sync', NULL);

      $msg = "syncResponse is: " . print_r($syncResponse, TRUE) . "\n";
      $logger->info($msg);
      $uri = trim($syncResponse['uri'], '/');
      $msg = "Import URI is: $uri\n";
      $logger->info($msg);
      return $uri;

    } catch (Exception $e) {
      $msg = "Sync Failed! Caught Exception: " . $e->getMessage();
      $logger->error($msg);

      for ($retries; $retries <= $maxRetries; $retries++) {
        try {
          // Attempt to sync.
          $bulkApi->sync();

          // Poll for sync status response.
          $currentStatus = $this->getBulkApiStatus($bulkApi);

          // A successful status response is either 'success' or 'warning'.
          if ($currentStatus === 'success' || $currentStatus === 'warning') {
            $msg = "Status Returned: " . $currentStatus;
            $logger->info($msg);
            $msg = "Sync completed successfully!";
            $logger->info($msg);
            $syncResponse = $bulkApi->getResponse('sync', NULL);
            $uri = trim($syncResponse['uri'], '/');
            $logger->info($msg);
            return $uri;
          }

        } catch (Exception $e) {
          $msg = "Sync retry attempt #$retries returned status: $currentStatus.";
          $msg .= "Sync Failed! Caught Exception: " . $e->getMessage();

          $logger->error($msg);

          // Retrieving processing log of last bulk API transfer.
          $msg = $bulkApi->log();
          $logger->error($msg);

          // Throw an exception and let core re-queue.
          throw new EloquaAppCloudQueueException("Eloqua bulk sync retry attempt #$retries returned status: $currentStatus.");
        }
      }

    }
  }

  /**
   * Retrieve the bulkApi's sync() status.
   *
   *  $bulkApi->status(true) blocks execution until a success or failure
   *    status is returned by Eloqua
   *
   * @param {Object} $bulkApi
   *   The bulkApi client object.
   *
   * @return {String} status
   *   Sync status reponse from Eloqua after calling $bulkApi->sync()
   */
  protected function getBulkApiStatus($bulkApi) {
    // Poll for bulkApi status response (blocking).
    $statusArr = $bulkApi->status(TRUE);
    return $statusArr['status'];
  }

  /**
   * Eloqua sends us the instance ID as a guid with dashes but does not want
   * dashes back.
   *
   * @param $guid
   */
  protected function formatGuid($guid) {
    $guid = str_replace('-', '', $guid);
    return $guid;
  }

}
