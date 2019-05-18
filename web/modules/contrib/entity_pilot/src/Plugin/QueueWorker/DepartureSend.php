<?php

namespace Drupal\entity_pilot\Plugin\QueueWorker;

use Drupal\entity_pilot\EntityPilotQueueWorkerBase;

/**
 * Queue worker.
 *
 * @QueueWorker(
 *   id = "entity_pilot_departures",
 *   title = @Translation("Entity Pilot departures"),
 *   cron = {"time" = 60}
 * )
 */
class DepartureSend extends EntityPilotQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->airTrafficControl->takeoff($data);
  }

}
