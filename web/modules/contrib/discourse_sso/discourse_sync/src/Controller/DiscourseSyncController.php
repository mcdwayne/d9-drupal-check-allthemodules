<?php

namespace Drupal\discourse_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\discourse_sync\UserEvent;
use Drupal\Core\Config\ConfigFactory;

/**
 * Discourse Sync controller.
 */
class DiscourseSyncController extends ControllerBase {

  const DISCOURSE_USER_CREATED_EVENT = 'user_created';

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;
  
  protected $webhookSecret;

  public function __construct(EventDispatcherInterface $eventDispatcher,
    ConfigFactory $config) {
    $this->eventDispatcher = $eventDispatcher;
    $this->webhookSecret = $config->get('discourse_sync.settings')->get('webhook_secret');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('config.factory')
    );
  }
  
  public function userWebhook() {
    $request = \Drupal::request();
    $discourse_event = $request->headers->get('x-discourse-event');
    if ($discourse_event !== self::DISCOURSE_USER_CREATED_EVENT) {
      return [];
    }

    $discourse_event_signature = $request->headers->get('x-discourse-event-signature');
    $discourse_payload_raw = file_get_contents('php://input');
    $signature = 'sha256=' . hash_hmac('sha256', $discourse_payload_raw, $this->webhookSecret);
    if ($signature !== $discourse_event_signature) {
      return [];
    }

    $payload = json_decode($discourse_payload_raw, TRUE);
    $username = $payload['user']['username'];
    $event = new UserEvent(user_load_by_name($username));
    $event = $this->eventDispatcher->dispatch(UserEvent::EVENT, $event);
    
    return [];
  }
}
