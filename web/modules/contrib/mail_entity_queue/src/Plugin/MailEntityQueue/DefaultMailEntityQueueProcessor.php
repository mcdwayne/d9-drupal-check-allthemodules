<?php

namespace Drupal\mail_entity_queue\Plugin\MailEntityQueue;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface;
use Drupal\mail_entity_queue\Event\MailEntityQueueItemEvent;
use Drupal\mail_entity_queue\Event\MailEntityQueueItemEvents;
use Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a default mail queue processor.
 *
 * @MailEntityQueueProcessor(
 *   id = "mail_entity_queue_default",
 *   label = @Translation("Default mail queue processor"),
 *   description = @Translation("Provides a default mail queue processor."),
 * )
 */
class DefaultMailEntityQueueProcessor extends PluginBase implements MailEntityQueueProcessorInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The mail queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $queueStorage;

  /**
   * The mail queue item storage.
   *
   * @var \Drupal\mail_entity_queue\MailEntityQueueItemEntityStorage
   */
  protected $queueItemStorage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a DefaultMailEntityQueueProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, MailManagerInterface $mail_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->eventDispatcher = $event_dispatcher;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->queueStorage = $entity_type_manager->getStorage('mail_entity_queue');
    $this->queueItemStorage = $entity_type_manager->getStorage('mail_entity_queue_item');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('plugin.manager.mail'),
      $container->get('logger.factory')->get('mail_entity_queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem(MailEntityQueueItemInterface $item, int $delay = 0) {
    if ((integer) $item->getStatus() === MailEntityQueueItemInterface::SENT) {
      $this->logger->error($this->t('Mail @name already has been sent. Skipping.', ['@name' => $item->label()]));
      return FALSE;
    }

    $params = $item->getData();
    $params['mail_entity_queue'] = $item->queue();

    if (empty($params['langcode'])) {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    else {
      $langcode = $params['langcode'];
    }

    $reply = NULL;
    if (!empty($params['reply'])) {
      $reply = $params['reply'];
    }

    // Make sure we can update this item in hook_mail() or hook_mail_alter().
    $params['mail_entity_queue_item'] = $item->id();

    $message = $this->mailManager->mail('mail_entity_queue', 'default_mail_entity_queue_processor', $item->getMail(), $langcode, $params, $reply, TRUE);

    // @TODO Make attempts configurable.
    $item->setAttempts($item->getAttempts() + 1);
    if (!$message['result'] && $item->getAttempts() < 5) {
      $item->setStatus(MailEntityQueueItemInterface::RETRYING);
      $this->dispatchEvent($item, MailEntityQueueItemEvents::MAIL_ENTITY_QUEUE_ITEM_PROCESSED_WRONGLY);
    }
    elseif (!$message['result']) {
      $item->setStatus(MailEntityQueueItemInterface::DISCARDED);
      $this->dispatchEvent($item, MailEntityQueueItemEvents::MAIL_ENTITY_QUEUE_ITEM_DISCARDED);
    }
    else {
      $item->setStatus(MailEntityQueueItemInterface::SENT);
      $this->dispatchEvent($item, MailEntityQueueItemEvents::MAIL_ENTITY_QUEUE_ITEM_PROCESSED_SUCCESSFULLY);
    }

    $item->save();

    // Congestion control.
    usleep($delay);

    return $message['result'];
  }

  /**
   * {@inheritdoc}
   */
  public function processQueue(string $mail_entity_queue) {
    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueInterface $queue */
    $queue = $this->queueStorage->load($mail_entity_queue);

    $ids = $this->queueItemStorage->getQuery()
      ->condition('queue', $mail_entity_queue)
      ->condition('status', [MailEntityQueueItemInterface::SENT, MailEntityQueueItemInterface::DISCARDED], 'NOT IN')
      ->sort('changed', 'ASC')
      ->range(0, $queue->getCronItems())
      ->execute();

    if ($ids) {
      $items = $this->queueItemStorage->loadMultiple($ids);
      foreach ($items as $item) {
        /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $item */
        $this->processItem($item, $queue->getCronDelay());
      }
    }
  }

  /**
   * Notify other modules the result of the process.
   *
   * @param \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface $item
   *   The element that generates this event.
   * @param string $event_name
   *   The name of the event to dispatch.
   */
  protected function dispatchEvent(MailEntityQueueItemInterface $item, string $event_name) {
    $event = new MailEntityQueueItemEvent($item);
    $this->eventDispatcher->dispatch($event_name, $event);
  }

}
