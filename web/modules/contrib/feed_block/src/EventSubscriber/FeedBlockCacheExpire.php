<?php

namespace Drupal\feed_block\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FeedBlockCacheExpire.
 *
 * @package Drupal\feed_block
 */
class FeedBlockCacheExpire implements EventSubscriberInterface {

  /**
   * The language manager object for retrieving the correct language code.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A policy rule determining the cacheability of a request.
   *
   * @var \Drupal\Core\PageCache\RequestPolicyInterface
   */
  protected $requestPolicy;

  /**
   * A policy rule determining the cacheability of the response.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface
   */
  protected $responsePolicy;

  /**
   * The cache contexts manager service.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContexts;

  /**
   * Whether to send cacheability headers for debugging purposes.
   *
   * @var bool
   */
  protected $debugCacheabilityHeaders = FALSE;

  /**
   * Constructs the FeedBlockCacheExpire object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager object for retrieving the correct language code.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
   *   A policy rule determining the cacheability of a request.
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
   *   A policy rule determining the cacheability of a response.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cache_contexts_manager
   *   The cache contexts manager service.
   * @param bool $http_response_debug_cacheability_headers
   *   (optional) Whether to send cacheability headers for debugging purposes.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, CacheContextsManager $cache_contexts_manager, $http_response_debug_cacheability_headers = FALSE) {
    $this->languageManager = $language_manager;
    $this->config = $config_factory->get('system.performance');
    $this->requestPolicy = $request_policy;
    $this->responsePolicy = $response_policy;
    $this->cacheContextsManager = $cache_contexts_manager;
    $this->debugCacheabilityHeaders = $http_response_debug_cacheability_headers;
  }

  /**
   * Sets extra headers on successful responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {

    $request = $event->getRequest();
    $response = $event->getResponse();

    $is_cacheable = $response instanceof CacheableResponseInterface && ($this->requestPolicy->check($request) === RequestPolicyInterface::ALLOW) && ($this->responsePolicy->check($response, $request) !== ResponsePolicyInterface::DENY);

    if ($is_cacheable) {
      $response_cacheability = $response->getCacheableMetadata();
      $tags = $response_cacheability->getCacheTags();
      // Only act on pages that contain a feed_block.
      if (in_array('feed_block', $tags)) {
        $this->setExpiresCacheLifetime($response);
      }
    }

  }

  /**
   * Set cache lifetime to cache.page.max_age.
   *
   * This overrides the default logic provided by Internal Page Cache.
   * See https://www.drupal.org/project/drupal/issues/2862894
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   A response object.
   */
  protected function setExpiresCacheLifetime(Response $response) {
    $cache_expiration = \Drupal::config('feed_block')->get('cache_expiration');
    if (!$cache_expiration) {
      // Default to 1 day feed cache if not specified in settings.
      $cache_expiration = 86400;
    }
    $response->setExpires(\DateTime::createFromFormat('U', time() + $cache_expiration));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
