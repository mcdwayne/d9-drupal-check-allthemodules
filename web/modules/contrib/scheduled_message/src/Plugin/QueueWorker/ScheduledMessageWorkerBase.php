<?php

namespace Drupal\scheduled_message\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for queue workers to send messages at appropriate time.
 */
abstract class ScheduledMessageWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * EntityStorage for message entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $messageStorage;

  /**
   * MessageNotifier Service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $messageStorage, MessageNotifier $messageNotifier) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messageStorage = $messageStorage;
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * Class factory.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('message'),
      $container->get('message_notify.sender')
    );
  }

  /**
   * Process a single scheduled message.
   *
   * @inheritdoc
   */
  public function processItem($data) {
    /** @var \Drupal\message\MessageInterface $message */
    $message = $this->messageStorage->load($data->id);
    if ($message->field_send_state->value == 'queued') {
      $this->messageNotifier->send($message);
      $message->field_send_state = 'sent';
      $message->save();
    }
  }

}
