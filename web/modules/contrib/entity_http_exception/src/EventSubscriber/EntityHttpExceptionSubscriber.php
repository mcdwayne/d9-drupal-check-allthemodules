<?php

namespace Drupal\entity_http_exception\EventSubscriber;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\entity_http_exception\Utils\EntityHttpExceptionUtils as Utils;

/**
 * EntityHttpExceptionSubscriber class.
 */
class EntityHttpExceptionSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Forum settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['entity_http_exception.entity.view'][] = [
      'exceptionOnEntities',
      74,
    ];
    return $events;
  }

  /**
   * Fires http exception when entity_http_exception.entity.view dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   */
  public function exceptionOnEntities(Event $event) {

    $config = $this->configFactory->get('entity_http_exception.settings');
    $is_anonymous = $this->currentUser->isAnonymous();

    $entity = $event->getEntity();
    $http_exception_code = $config->get(Utils::getHttpExceptionCodeKey($entity->getEntityTypeId(), $entity->bundle()));
    if ($is_anonymous && $http_exception_code != 0) {
      $event->setResponse($http_exception_code);
    }

  }

}
