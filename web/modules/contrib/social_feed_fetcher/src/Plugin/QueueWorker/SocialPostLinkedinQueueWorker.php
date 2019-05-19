<?php

namespace Drupal\social_feed_fetcher\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\social_feed_fetcher\PluginNodeProcessorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *  id = "social_posts_linkedin_queue_worker",
 *  title = @Translation("Social Posts Queue Worker"),
 *  cron = {"time" = 10},
 * )
 */
class SocialPostLinkedinQueueWorker extends SocialPostQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\social_feed_fetcher\Plugin\NodeProcessor\LinkedinNodeProcessor $linkedin_processor */
    $linkedin_processor = $this->nodeProcessor->createInstance('linkedin_processor');
    $linkedin_processor->processItem('linkedin', $data);
  }

}
