<?php

namespace Drupal\sms_ui\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms_ui\Entity\SmsHistory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Save SMS messages to history as specified.
 */
class HistoryEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Flag to mark that an existing history item may already have been saved.
   *
   * @var bool
   */
  protected $alreadySaved = FALSE;

  /**
   * The current SMS History being managed.
   *
   * It is assumed that everything that happens in a single request all belong
   * to the same history.
   *
   * @var \Drupal\sms_ui\Entity\SmsHistoryInterface
   */
  protected $currentHistory;

  /**
   * The expiry date calculated for messages.
   *
   * @var int
   */
  protected $expiry;

  /**
   * HistoryEventSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * Processes outgoing messages.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   */
  public function processOutgoingHistory(SmsMessageEvent $event) {
    $this->saveMessageHistory($event, 'sent');
  }

  /**
   * Processes queued messages.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   */
  public function processQueuedHistory(SmsMessageEvent $event) {
    $this->saveMessageHistory($event);
  }

  /**
   * Adds messages that have been queued to the current history and returns it.
   *
   * Even though the message may have been queued, a gateway with skip_queue
   * turned on would still send the message immediately.
   *
   * @param \Drupal\sms\Event\SmsMessageEvent $event
   *   The SMS message event holding SMS messages.
   *
   * @param string $status|null
   *   The new status to be updated. NULL means no change.
   *
   * @return \Drupal\sms_ui\Entity\SmsHistoryInterface
   */
  protected function saveMessageHistory(SmsMessageEvent $event, $status = NULL) {
    $history = $this->getExistingHistory();
    foreach ($event->getMessages() as $sms_message) {
      // Messages that do not have history should be attached to the
      // existing history.
      $msg_history = NULL;
      if (!$sms_message instanceof SmsMessageInterface || ($msg_history = SmsHistory::getHistoryForMessage($sms_message)) ===  NULL) {
        // Even for plain messages, they can be double added if gateway
        // _skip_queue is true, so check for this as well.
        if (!$sms_message->getOption('_already_saved')) {
          $history->addSmsMessage($sms_message);
          $sms_message->setOption('_already_saved', TRUE);
        }
      }
      if ($msg_history !== NULL) {
        if ($status) {
          $msg_history->setStatus($status);
        }
        $msg_history->save();
      }
    }
    if ($history->getSmsMessages()) {
      if ($status) {
        $history->setStatus($status);
      }
      $history->save();
    }
    return $history;
  }

  /**
   * Gets the existing SMS message history or creates a new one.
   */
  protected function getExistingHistory() {
    // Get statically cached history first if it exists. In the current
    // request, there can only be one history object for all SMS.
    if (isset($this->currentHistory)) {
      return $this->currentHistory;
    }
    
    // Get history cloned from an existing record (draft or sent). This should
    // be represented by a url query argument.
    if ($this->requestStack->getCurrentRequest()->query->has('_stored')) {
      $history = SmsHistory::load($this->requestStack->getCurrentRequest()->query->get('_stored'));
      if ($history->getStatus() === 'sent') {
        // For sent items, a duplicate needs to be created since the original
        // has to be retained.
        $history = $history->createDuplicate()->setSmsMessages([]);
      }
      else {
        // For draft messages, we have to delete the existing messages and
        // re-create new ones since SMS framework does not have the ability to
        // update them.
        $history->deleteSmsMessages();
      }
      $history
          ->setExpiry($this->getDefaultMessageExpiry())
          ->setStatus('queued');
      return $this->currentHistory = $history;
    }
    
    // Failing all of the above, create a new history item.
    $history = SmsHistory::create()
      ->setExpiry($this->getDefaultMessageExpiry())
      ->setStatus('queued');

    return $this->currentHistory = $history;
  }

  /**
   * Returns the default message expiry date.
   *
   * @return int
   */
  protected function getDefaultMessageExpiry() {
    if (!isset($this->expiry)) {
      // If retention time is not specified or is indefinite, use the maximum
      // possible integer value for the expiry time.
      $retention = $this->configFactory
        ->get('sms_ui.settings')
        ->get('message_history.retention');
      // @TODO Find a proper MAX_INT value to use.
      $this->expiry = ($retention) ? $retention * 86400 + REQUEST_TIME : 10 * 365 * 86400 + REQUEST_TIME;
    }
    return $this->expiry;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SmsEvents::MESSAGE_OUTGOING_POST_PROCESS][] = ['processOutgoingHistory', 1500];
    $events[SmsEvents::MESSAGE_QUEUE_POST_PROCESS][] = ['processQueuedHistory', -1500];
    return $events;
  }

  /**
   * Gets the current statically cached history entity.
   *
   * @return \Drupal\sms_ui\Entity\SmsHistoryInterface|null
   */
  public function getHistoryEntity() {
    return $this->currentHistory;
  }

}
