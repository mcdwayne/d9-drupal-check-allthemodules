<?php

namespace Drupal\votingapi_queue\Plugin\QueueWorker;

/**
 * The VotingAPI cron implementation class.
 *
 * @QueueWorker(
 *   id = "votingapi_queue_cron",
 *   title = @Translation("VotingAPI queue cron"),
 *   cron = {"time" = 60}
 * )
 */
class VotingapiCronQueueWorker extends VotingapiQueueWorker {

}
