<?php

namespace Drupal\slack_rtm\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\slack_rtm\Entity\SlackRtmMessageCreate;

/**
 * Updates a feed's items.
 *
 * @QueueWorker(
 *   id = "slack_rtm_queue",
 *   title = @Translation("Slack RTM Queue"),
 *   cron = {"time" = 60}
 * )
 */
class SlackRtmQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $result = (new SlackRtmMessageCreate($data))->generateEntity();
  }
}