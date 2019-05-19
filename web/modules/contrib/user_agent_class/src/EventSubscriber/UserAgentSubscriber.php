<?php

namespace Drupal\user_agent_class\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Component\Utility\Xss;
use Drupal\user_agent_class\CheckAgentServices;
use Drupal\Core\Config\ConfigFactory;

/**
 * Class UserAgentSubscriber.
 */
class UserAgentSubscriber implements EventSubscriberInterface {

  /**
   * Language manage.
   *
   * @var \Drupal\user_agent_class\CheckAgentServices
   */
  protected $classes;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Send names of classes.
   *
   * @var string
   */
  static protected $userAgentClass;

  /**
   * Constructs a new UserAgentSubscriber object.
   *
   * @param \Drupal\user_agent_class\CheckAgentServices $classes
   *   Migrate service.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config Factory.
   */
  public function __construct(CheckAgentServices $classes, ConfigFactory $configFactory) {
    $this->classes = $classes;
    $this->configFactory = $configFactory;
  }

  /**
   * Code that should be triggered on event specified.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The response event, which contains the current request.
   */
  public function onRequest(GetResponseEvent $event) {
    $methodProvide = $this->configFactory->get('user_agent_class.provide')->get('user_agent_class.responsibility_frontend_backend');
    if ($methodProvide) {
      $request = $event->getRequest()->headers->get('user-agent');
      $filterUserAgent = Xss::filter($request);
      $response = $this->classes->checkUserAgent($filterUserAgent);
      self::$userAgentClass = $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Get user agent class.
   *
   * @return string
   *   User agent class for hook_preprocess_html.
   */
  public static function getUserAgentClass() {
    return self::$userAgentClass;
  }

}
