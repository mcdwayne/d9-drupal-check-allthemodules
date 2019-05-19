<?php

namespace Drupal\update_runner\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\update_runner\Entity\UpdateRunnerJob;
use Drupal\update_runner\Event\UpdateRunnerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle update runner events.
 */
class UpdateRunnerEventSubscriber implements EventSubscriberInterface {

  /**
   * Language Manager.
   *
   * @var \Drupal\update_runner\EventSubscriber\LanguageManagerInterface
   */

  protected $languageManager;

  /**
   * UpdateRunnerEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager.
   * @param \Drupal\update_runner\EventSubscriber\LanguageManagerInterface $language_manager
   *   Language manger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Get processor settings.
   *
   * @param string $processorId
   *   Processor id.
   * @param string $setting
   *   Setting to return.
   *
   * @return mixed
   *   Configuration setting used.
   */
  private function getProcessorSetting($processorId, $setting) {
    $processor = $this->configFactory->get('update_runner.update_runner_processor.' . $processorId);
    return unserialize($processor->get('data'))[$setting];
  }

  /**
   * Sends the email.
   *
   * @param Drupal\update_runner\Entity\UpdateRunnerJob $entity
   *   Job.
   * @param string $processorId
   *   Processor id.
   * @param string $key
   *   Event key.
   */
  private function sendEmail(UpdateRunnerJob $entity, $processorId, $key) {
    $to = $this->configFactory->get('system.site')->get('mail');
    $params['processor_id'] = $processorId;
    $params['job_data'] = $entity->get('data')->value;
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $send = TRUE;
    $this->mailManager->mail('update_runner', $key, $to, $langcode, $params, NULL, $send);
  }

  /**
   * Event subscriber for job creation.
   *
   * @param \Drupal\update_runner\Event\UpdateRunnerEvent $event
   *   Event.
   */
  public function onJobCreated(UpdateRunnerEvent $event) {
    /** @var \Drupal\update_runner\Entity\UpdateRunnerJob $entity */
    $entity = $event->getEntity();
    $processorId = $entity->get('processor')->value;

    // Gets setting.
    $notifyOnCreate = $this->getProcessorSetting($processorId, 'notify_on_create');

    if ($notifyOnCreate) {
      \Drupal::logger('update_runner')->notice('New update runner job created for ' . $processorId);
      $this->sendEmail($entity, $processorId, 'job_created');
    }
  }

  /**
   * Event subscriber for job completed.
   *
   * @param \Drupal\update_runner\Event\UpdateRunnerEvent $event
   *   Job.
   */
  public function onJobCompleted(UpdateRunnerEvent $event) {
    /** @var \Drupal\update_runner\Entity\UpdateRunnerJob $entity */
    $entity = $event->getEntity();
    $processorId = $entity->get('processor')->value;

    // Gets setting.
    $notifyOnComplete = $this->getProcessorSetting($processorId, 'notify_on_complete');

    if ($notifyOnComplete) {
      \Drupal::logger('update_runner')->notice('Job update runner executed for ' . $processorId);
      $this->sendEmail($entity, $processorId, 'job_completed');
    }
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * @return array
   *   The event names to listen to
   */
  public static function getSubscribedEvents() {
    $events[UpdateRunnerEvent::UPDATE_RUNNER_JOB_CREATED][] = ['onJobCreated'];
    $events[UpdateRunnerEvent::UPDATE_RUNNER_JOB_COMPLETED][] = ['onJobCompleted'];
    return $events;
  }

}
