<?php

namespace Drupal\getjwtonlogin\EventSubscriber;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Xml;
use Drupal\Component\Serialization\Json;

/**
 * Class ExampleLoginListener.
 *
 * @package Drupal\example
 */
class JwtLoginSubscriber implements EventSubscriberInterface
{

  /**
   * @var path.current service
   */
  private $currentPath;
  /**
   * @var jwt.authentication.jwt service
   */
  private $jwtAuth;

  /**
   * Constructor with dependency injection
   */
  public function __construct($currentPath, $JwtAuth) {
    $this->currentPath = $currentPath;
    $this->jwtAuth = $JwtAuth;
  }

  /**
   * Add JWT access token to user login API response
   */
  public function onHttpLoginResponse(FilterResponseEvent $event) {
    // Halt if not user login request
    if ($this->currentPath->getPath() !== '/user/login') {
      return;
    }
    // Get response
    $response = $event->getResponse();
    // Ensure not error response
    if ($response->getStatusCode() !== 200) {
      return;
    }
    // Get request
    $request = $event->getRequest();
    // Just handle JSON format for now
    if ($request->query->get('_format') !== 'json') {
      return;
    }

    //\Drupal::logger('getjwtonlogin')->notice("injecting JWT");

    // Decode and add JWT token
    if ($content = $response->getContent()) {
      if ($decoded = Json::decode($content)) {
        // Add JWT access_token
        $access_token = $this->jwtAuth->generateToken();
        $decoded['access_token'] = $access_token;
        // Set new response JSON
        $response->setContent(Json::encode($decoded));
        $event->setResponse($response);
      }
    }
  }

  /**
   * The subscribed events.
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['onHttpLoginResponse'];
    return $events;
  }

}
