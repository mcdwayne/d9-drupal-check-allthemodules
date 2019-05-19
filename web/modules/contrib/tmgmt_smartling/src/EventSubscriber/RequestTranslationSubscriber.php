<?php

namespace Drupal\tmgmt_smartling\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartling\Context\TranslationJobToUrl;
use Drupal\tmgmt_smartling\Event\RequestTranslationEvent;
use Drupal\Core\Queue\QueueFactory;
use Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator;

class RequestTranslationSubscriber implements EventSubscriberInterface {

  const WAIT_BEFORE_CONTEXT_UPLOAD = 600;

  protected $contextUploadQueue;


  public function __construct(QueueFactory $queue, TranslationJobToUrl $url_converter) {
    $this->contextUploadQueue = $queue->get('smartling_context_upload', TRUE);
    $this->urlConverter = $url_converter;
  }

  /**
   * Code that should be triggered on event specified
   */
  public function onUploadRequest(RequestTranslationEvent $event) {
    /** @var JobInterface $job */
    $job = $event->getJob();
    if (!($job->getTranslator()->getPlugin() instanceof SmartlingTranslator)) {
      return;
    }

    $job_items = $job->getItems();
    if (empty($job_items)) {
      return;
    }

    $filename = $job->getTranslatorPlugin()->getFileName($job);
    foreach ($job_items as $item) {
      $url = $this->urlConverter->convert($item);
      $cloned_item = clone $item;
      \Drupal::moduleHandler()->alter('tmgmt_smartling_context_url', $url, $cloned_item);

      if (!empty($url)) {
        $this->contextUploadQueue->createItem([
          'url' => $url,
          'filename' => $filename,
          'job_id' => $job->id(),
          'upload_date' => time() + self::WAIT_BEFORE_CONTEXT_UPLOAD,
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // For this example I am using KernelEvents constants (see below a full list).
    $events = [];
    $events[RequestTranslationEvent::REQUEST_TRANSLATION_EVENT][] = ['onUploadRequest'];
    return $events;
  }

}
