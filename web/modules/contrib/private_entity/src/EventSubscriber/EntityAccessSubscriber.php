<?php

namespace Drupal\private_entity\EventSubscriber;

use Drupal\Core\Access\AccessManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;

/**
 * Class EntityAccessSubscriber.
 */
class EntityAccessSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Access\AccessManagerInterface definition.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Path\PathMatcherInterface definition.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new EntityAccessSubscriber object.
   */
  public function __construct(AccessManagerInterface $access_manager, ConfigFactoryInterface $config_factory, PathMatcherInterface $path_matcher) {
    $this->accessManager = $access_manager;
    $this->configFactory = $config_factory;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.response'] = ['on403'];
    return $events;
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event to process.
   */
  public function on403(Event $event) {
    // @todo implement redirection to user login with destination
    // @todo review http://cgit.drupalcode.org/drupal/tree/core/lib/Drupal/Core/EventSubscriber/CustomPageExceptionHtmlSubscriber.php
    $config = $this->configFactory->get('private_entity.settings');
    // drupal_set_message($config->get('user_login_redirect'));
    // drupal_set_message('Event kernel.response thrown by Subscriber
    // in module private_entity.', 'status', TRUE);.
  }

}
