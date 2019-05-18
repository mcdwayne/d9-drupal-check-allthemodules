<?php

namespace Drupal\i18n_sso\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber SsoAllowOrigin.
 */
class SsoAllowOrigin implements EventSubscriberInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SsoAllowOrigin object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Adds header "Allow-origin" to allow XHR requests across website domains.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event.
   */
  public function onRespond(FilterResponseEvent $event) {
    $allowedDomains = $this->configFactory
      ->getEditable('language.negotiation')
      ->get('url.domains');

    $response = $event->getResponse();
    $request = $event->getRequest();
    $origin = $request->get('origin');
    $domain = str_replace('http://', '', $origin);
    $domain = str_replace('https://', '', $domain);
    if (in_array($domain, $allowedDomains)) {
      $response->headers->set('Access-Control-Allow-Origin', $origin);
      $response->headers->set('Access-Control-Allow-Headers', 'Accept,Origin,Content-Type,Cookie');
      $response->headers->set('Access-Control-Allow-Credentials', 'true');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
