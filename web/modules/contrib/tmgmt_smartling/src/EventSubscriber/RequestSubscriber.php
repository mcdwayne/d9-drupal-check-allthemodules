<?php

namespace Drupal\tmgmt_smartling\EventSubscriber;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tmgmt_smartling\SmartlingTranslatorUi;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RequestSubscriber.
 *
 * Needed for health check and show warning message.
 *
 * @package Drupal\tmgmt_smartling\EventSubscriber
 */
class RequestSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  const CRON_LAST_RUN_THRESHOLD = 600;
  const QUEUE_THRESHOLD = 250;

  /**
   * @var QueueFactory
   */
  private $queueFactory;

  /**
   * @var AccountInterface
   */
  private $user;

  public function __construct(QueueFactory $queue, AccountInterface $user) {
    $this->queueFactory = $queue;
    $this->user = $user;
  }

  /**
   * Reacts on page load event.
   */
  public function init() {
    $http_x_requested_with = \Drupal::request()->server->get('HTTP_X_REQUESTED_WITH');

    if (empty($http_x_requested_with) && $this->user->hasPermission('see smartling messages')) {
      $last_cron_run = time() - \Drupal::state()->get('system.cron_last');
      $show_cron_message = FALSE;
      $show_queue_message = FALSE;

      // Check last cron run time.
      if ($last_cron_run > static::CRON_LAST_RUN_THRESHOLD) {
        $show_cron_message = TRUE;
      }

      // Check amount of queue items in each queue.
      $queues = SmartlingTranslatorUi::getSmartlingQueuesDefinitions();

      foreach ($queues as $name => $queue_definition) {
        $queue = $this->queueFactory->get($name);
        $items = $queue->numberOfItems();

        if ($items > static::QUEUE_THRESHOLD) {
          $show_queue_message = TRUE;

          break;
        }
      }

      // Assemble warning message.
      $message = NULL;

      if ($show_cron_message) {
        $message = $this->t('Last cron run happened more than 10 minutes ago.');
      }

      if ($show_queue_message) {
        $sub_message = $this->t('Some of the Smartling cron queues are overflowed.');
        $message = empty($message) ? $sub_message : $message . ' ' . $sub_message;
      }

      if (!empty($message)) {
        $message .= ' ' . $this->t('Configure your cron job to run once per 5-10 minutes in order to process Smartling queues more effectively. Please visit this <a href="http://docs.drush.org/en/master/cron/" target="_blank">page</a> for more information.');

        drupal_set_message(new TranslatableMarkup($message), 'warning');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['init'];

    return $events;
  }

}
