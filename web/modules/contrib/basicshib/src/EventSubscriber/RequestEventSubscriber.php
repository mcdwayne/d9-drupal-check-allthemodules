<?php

namespace Drupal\basicshib\EventSubscriber;

use Drupal\basicshib\AuthenticationHandlerInterface;
use Drupal\basicshib\AuthenticationManagerInterface;
use Drupal\basicshib\AuthorizationManagerServiceInterface;
use Drupal\basicshib\Exception\AttributeNotMappedException;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Created by PhpStorm.
 * User: th140
 * Date: 10/19/17
 * Time: 1:35 PM
 */
class RequestEventSubscriber implements EventSubscriberInterface {

  /**
   * @var AccountProxyInterface
   */
  private $current_user;

  /**
   * @var LoggerChannelFactoryInterface
   */
  private $logger_channel_factory;

  /**
   * @var AuthenticationHandlerInterface
   */
  private $authentication_handler;

  /**
   * AuthEventSubscriber constructor.
   *
   * @param AccountProxyInterface $current_user
   */
  public function __construct(AccountProxyInterface $current_user,
                              LoggerChannelFactoryInterface $logger_channel_factory,
                              AuthenticationHandlerInterface $authentication_handler
  ) {

    $this->current_user = $current_user;
    $this->logger_channel_factory = $logger_channel_factory;
    $this->authentication_handler = $authentication_handler;
  }

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = 'onRequest';
    return $events;
  }

  /**
   * Request handler.
   *
   * @param GetResponseEvent $event
   * @param $eventId
   * @param ContainerAwareEventDispatcher $dispatcher
   *
   * @throws \InvalidArgumentException
   */
  public function onRequest(GetResponseEvent $event, $eventId, ContainerAwareEventDispatcher $dispatcher) {
    $this->authentication_handler->checkUserSession(
      $event->getRequest(),
      $this->current_user
    );
  }
}
