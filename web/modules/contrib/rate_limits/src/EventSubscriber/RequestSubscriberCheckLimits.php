<?php

namespace Drupal\rate_limits\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Router;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rate_limits\Entity\RateLimitConfig;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Flood\DatabaseBackend;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

/**
 * Class RequestSubscriberCheckLimits.
 */
class RequestSubscriberCheckLimits implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Routing\Router
   */
  protected $router;

  /**
   * @var \Drupal\Core\Flood\DatabaseBackend
   */
  protected $flood;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new RequestSubscriberCheckLimits object.
   */
  public function __construct(Router $router, DatabaseBackend $flood, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->router = $router;
    $this->flood = $flood;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onRequest'];

    return $events;
  }

  /**
   * Check the limits on the request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   When the system is flooded.
   * @throws \Exception
   *   When the flood table does not exist.
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $route_data = $this->router->matchRequest($request);
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $route_data[RouteObjectInterface::ROUTE_OBJECT];
    $config = $this->loadConfig($route);
    if (!$config) {
      return;
    }
    if ($this->currentUser->hasPermission('skip rate limit checks')) {
      return;
    }
    $flood_config_route = $config->get('user_flood_route');
    $flood_config_global = $config->get('user_flood_global');
    $route_id = md5($route->getPath());
    // Create the rate limit event name.
    $rate_limit_ip_id = 'rate_limit_ip:' . $route_id;
    $rate_limit_user_id = 'rate_limit_user:' . $route_id;

    if (!$this->checkIp($rate_limit_ip_id, $flood_config_route, $event)) {
      return;
    }
    if (!$this->checkUser($rate_limit_user_id, $flood_config_route, $event)) {
      return;
    }
    $hash = md5(implode(',', $config->get('tags')));
    if (!$this->checkIp('rate_limit_global:' . $hash, $flood_config_global, $event)) {
      return;
    }
    $this->checkUser('rate_limit_global:' . $hash, $flood_config_global, $event);
  }

  /**
   * Loads the rate limit configuration based on the tags.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to be rate limited.
   *
   * @return \Drupal\rate_limits\Entity\RateLimitConfig|NULL
   *   The rate limit.
   */
  protected function loadConfig(Route $route) {
    $route_tags = $route->getOption('tags');
    if (empty($route_tags)) {
      return NULL;
    }
    // Load all rate limits.
    $storage = $this->entityTypeManager->getStorage('rate_limit_config');
    $query = $storage->getQuery();
    $ids = array_values($query->execute());
    if (empty($ids)) {
      return NULL;
    }
    /** @var \Drupal\rate_limits\Entity\RateLimitConfig[] $configs */
    $configs = $storage->loadMultiple($ids);
    // Find the config that has all tags.
    foreach ($configs as $config) {
      $config_tags = $config->get('tags');
      $all_found = array_reduce(
        $config_tags,
        function ($res, $route_tag) use ($route_tags) {
          return $res && in_array($route_tag, $route_tags);
        },
        TRUE
      );
      if ($all_found) {
        return $config;
      }
    }
    // If we got to this point it means that we could not find the config.
    return NULL;
  }

  /**
   * Check the rate limits for an IP address.
   *
   * @param string $event_name
   *   The event name.
   * @param array $flood_config
   *   The configuration with the limits.
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   *
   * @return bool
   *   Indicates if the request is allowed.
   *
   * @throws \Exception
   *   When the flood table is not created.
   */
  protected function checkIp($event_name, $flood_config, GetResponseEvent $event) {
    $allowed = $this->flood->isAllowed(
      $event_name,
      $flood_config['ip_limit'],
      $flood_config['ip_window']
    );
    if (!$allowed) {
      // Do not process further, fail fast.
      $response = new Response('Too many requests', 429);
      $event->setResponse($response);
      return FALSE;
    }
    $this->flood->register($event_name, $flood_config['ip_window']);
    return TRUE;
  }

  /**
   * Check the rate limits for a user.
   *
   * @param string $event_name
   *   The event name.
   * @param array $flood_config
   *   The configuration with the limits.
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   *
   * @return bool
   *   Indicates if the request is allowed.
   *
   * @throws \Exception
   *   When the flood table is not created.
   */
  protected function checkUser($event_name, $flood_config, GetResponseEvent $event) {
    if ($this->currentUser->isAnonymous()) {
      // The user check does not apply to anonymous.
      return TRUE;
    }
    $request = $event->getRequest();
    if ($flood_config['uid_only']) {
      // Register flood events based on the uid only, so they apply for any
      // IP address. This is the most secure option.
      $identifier = $this->currentUser->id();
    }
    else {
      // The default identifier is a combination of uid and IP address. This
      // is less secure but more resistant to denial-of-service attacks that
      // could lock out all users with public user names.
      $identifier = $this->currentUser->id() . '-' . $request->getClientIP();
    }

    $allowed = $this->flood->isAllowed(
      $event_name,
      $flood_config['user_limit'],
      $flood_config['user_window'],
      $identifier
    );
    if (!$allowed) {
      // Do not process further, fail fast.
      $response = new Response('Too many requests', 429);
      $event->setResponse($response);
      return FALSE;
    }
    $this->flood->register($event_name, $flood_config['user_window'], $identifier);
    return TRUE;
  }

}
