<?php

namespace Drupal\webform_digests;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The webform digest queue builder.
 */
class WebformDigestsQueueBuilder {

  private $webformDigestStorage;
  private $queueFactory;
  private $settings;

  /**
   * Creates an instance of the webform digest builder.
   *
   * @param EntityTypeManager $entityTypeManager
   *   The entity type manager for getting storage classes.
   */
  public function __construct(EntityTypeManager $entityTypeManager, QueueFactory $queue_factory, ConfigFactoryInterface $config_factory) {
    $this->webformDigestStorage = $entityTypeManager->getStorage('webform_digest');
    $this->queueFactory = $queue_factory;
    $this->settings = $config_factory->get('webform_digests.settings');
  }

  /**
   * Queue all submissions added within the given time window.
   *
   * @param int $startDate
   *   A timestamp to start the filter with - defaults to the digests frequency.
   * @param int $endDate
   *   An end date to end the submissions filter - defaults to now.
   */
  public function queueSubmissions($startDate = NULL, $endDate = NULL) {
    $endDate = (!empty($endDate)) ? $endDate : time();
    $digests = $this->webformDigestStorage->loadMultiple();
    $digestFrequency = $this->settings->get('cron.frequency');
    if (empty($digests)) {
      return;
    }
    $queue = $this->queueFactory->get('webform_digest_queue');
    foreach ($digests as $digest) {
      $startDate = ($startDate) ? $startDate : strtotime(' - 1 ' . $digestFrequency, $endDate);
      $queue->createItem([
        'digest' => $digest,
        'start' => $startDate,
        'end' => $endDate,
      ]);
    }
    return count($digests);
  }

}
