<?php

namespace Drupal\twitter_sync\Plugin\QueueWorker;

/**
 * Consume twitter api to rescue last 3 tweets.
 *
 * @QueueWorker(
 *   id = "cron_twitter_consumer",
 *   title = @Translation("Cron Tweet Consumer"),
 *   cron = {"time" = 25}
 * )
 */
class CronTwitterConsumer extends TweetConsumerBase {}
