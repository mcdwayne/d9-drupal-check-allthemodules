<?php

namespace Drupal\welcome_mail\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\user\Entity\User;

/**
 * Sends emails for welcome mail.
 *
 * @QueueWorker(
 *   id = "welcome_mail",
 *   title = @Translation("Send Welcome mail"),
 *   cron = {"time" = 60}
 * )
 */
class WelcomeMailQueue extends QueueWorkerBase {

  /**
   * Keep track of which ones are processed so we can suspend queue on finish.
   *
   * @var array
   */
  protected $processed = [];

  /**
   * Works on a single queue item.
   *
   * @param int $uid
   *   The data that was passed to
   *   \Drupal\Core\Queue\QueueInterface::createItem() when the item was queued.
   *
   * @throws \Drupal\Core\Queue\RequeueException
   *   Processing is not yet finished. This will allow another process to claim
   *   the item immediately.
   * @throws \Exception
   *   A QueueWorker plugin may throw an exception to indicate there was a
   *   problem. The cron process will log the exception, and leave the item in
   *   the queue to be processed again later.
   * @throws \Drupal\Core\Queue\SuspendQueueException
   *   More specifically, a SuspendQueueException should be thrown when a
   *   QueueWorker plugin is aware that the problem will affect all subsequent
   *   workers of its queue. For example, a callback that makes HTTP requests
   *   may find that the remote server is not responding. The cron process will
   *   behave as with a normal Exception, and in addition will not attempt to
   *   process further items from the current item's queue during the current
   *   cron run.
   *
   * @see \Drupal\Core\Cron::processQueues()
   */
  public function processItem($uid) {
    if (!$account = User::load($uid)) {
      return;
    }
    $config = \Drupal::configFactory()
      ->get('welcome_mail.settings');
    $hours = $config->get('time');
    // And this is hours.
    $time = $hours * 3600;
    // See if it is time.
    if ($account->getCreatedTime() > (\Drupal::time()->getRequestTime() - $time)) {
      if (!empty($this->processed[$uid])) {
        // Processed before. So we are running in circles.
        throw new SuspendQueueException('Suspending queue because we have no more new entries');
      }
      $this->processed[$uid] = TRUE;
      throw new RequeueException('There has not passed enough time for user uid ' . $uid);
    }
    /** @var \Drupal\Core\Mail\MailManager $mail_manager */
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $mail_manager->mail('welcome_mail', 'welcome', $account->getEmail(), $account->getPreferredLangcode());
  }

}
