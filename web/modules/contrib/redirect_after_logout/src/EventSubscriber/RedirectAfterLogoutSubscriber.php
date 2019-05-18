<?php

namespace Drupal\redirect_after_logout\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * RedirectAfterLogoutSubscriber event subscriber.
 *
 * @package Drupal\redirect_after_logout\EventSubscriber
 */
class RedirectAfterLogoutSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Check redirection.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event.
   */
  public function checkRedirection(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      $destination = &drupal_static('redirect_after_logout_user_logout');
      if ($destination) {
        if ($destination == '<front>') {
          $destination = Url::fromRoute($destination);
        }
        else {
          $destination = Url::fromUri('internal:' . $destination);
        }
        $config = $this->configFactory->get('redirect_after_logout.settings');
        $logout_message = $config->get('message', '');
        if (!empty($logout_message)) {
          $destination = $destination
            ->setOption('query', ['logout-message' => 1])
            ->toString();
        }
        else {
          $destination = $destination->toString();
        }
        $response->setTargetUrl($destination);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkRedirection'];
    return $events;
  }

}
