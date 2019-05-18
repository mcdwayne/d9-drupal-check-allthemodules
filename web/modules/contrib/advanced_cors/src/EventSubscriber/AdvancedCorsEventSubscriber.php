<?php

namespace Drupal\advanced_cors\EventSubscriber;

use Drupal\advanced_cors\Entity\RouteConfigEntityInterface;
use Drupal\advanced_cors\PatternsCache;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber for adding headers.
 */
class AdvancedCorsEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Patterns cache service.
   *
   * @var \Drupal\advanced_cors\PatternsCache
   */
  protected $patterns;

  /**
   * Constructs a new CORS response event subscriber.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\advanced_cors\PatternsCache $patterns
   *   The pattern cache.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityManagerInterface $entityManager, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, PatternsCache $patterns) {
    $this->storage = $entityManager->getStorage('route_config');
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->patterns = $patterns;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[KernelEvents::RESPONSE] = 'onResponse';

    return $events;
  }

  /**
   * Adds headers to the response.
   */
  public function onResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();
    $path_info = $request->getPathInfo();
    $current_path = $this->aliasManager->getPathByAlias($path_info);

    foreach ($this->patterns->getPatterns() as $pattern => $id) {
      if ($this->pathMatcher->matchPath($current_path, $pattern)) {
        $config = $this->storage->load($id);

        if ($config) {
          $this->addCorsHeaders($event, $config);
        }

        break;
      }
    }
  }

  /**
   * Adds CORS headers to the response.
   */
  protected function addCorsHeaders(FilterResponseEvent $event, RouteConfigEntityInterface $entity) {
    $mapping_headers = [
      'allowed_headers' => 'Access-Control-Allow-Headers',
      'allowed_methods' => 'Access-Control-Allow-Methods',
      'allowed_origins' => 'Access-Control-Allow-Origin',
      'exposed_headers' => 'Access-Control-Expose-Headers',
      'max_age' => 'Access-Control-Max-Age',
      'supports_credentials' => 'Access-Control-Allow-Credentials',
    ];
    $response = $event->getResponse();

    foreach ($mapping_headers as $config_name => $header) {
      $config = $entity->get($config_name);

      if ($config !== '') {
        $response->headers->set($header, $config, TRUE);
      }
    }
  }

}
