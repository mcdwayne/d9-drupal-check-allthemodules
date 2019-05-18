<?php

namespace Drupal\micro_sso\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\micro_sso\MicroSsoHelperInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * The micro SSO event subscriber.
 */
class MicroSsoSubscriber implements EventSubscriberInterface {

  /**
   * The micro sso helper.
   *
   * @var \Drupal\micro_sso\MicroSsoHelperInterface
   */
  protected $microSsoHelper;

  /**
   * Constructs an event subscriber object for allowing CORS request.
   *
   * @param \Drupal\micro_sso\MicroSsoHelperInterface $micro_sso_helper
   *   The micro sso helper service.
   */
  public function __construct(MicroSsoHelperInterface $micro_sso_helper) {
    $this->microSsoHelper = $micro_sso_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onRespond(FilterResponseEvent $event) {
    if ($this->microSsoHelper->isMaster()) {
      if ($origin = $this->microSsoHelper->getOrigin()) {
        // We had an origin in the request query, should not happen with images
        // or classical pages so we can be quite sure this was an ajax request.
        // We need to allow modern browsers to send cookies on theses ajax cross
        // domain requests.
        // Note that Access-Control-Allow-Origin: * would not work with
        // credentials, so we also need to send a domain specific authorization.
        // The method getOrigin() made the check about validity of this origin.
        $scheme = $this->microSsoHelper->getScheme();
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', $scheme . '://' . $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Headers', 'Accept,Origin,Content-Type,Cookie');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
      }
    }
  }

}
