<?php

namespace Drupal\entity_pilot\Plugin\QueueWorker;

use Drupal\entity_pilot\EntityPilotQueueWorkerBase;

/**
 * Queue worker.
 *
 * @QueueWorker(
 *   id = "entity_pilot_arrivals",
 *   title = @Translation("Entity Pilot arrivals"),
 *   cron = {"time" = 60}
 * )
 */
class ArrivalLand extends EntityPilotQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->airTrafficControl->land($data);
  }

}
