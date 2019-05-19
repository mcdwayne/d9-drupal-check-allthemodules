<?php
/**
 * @file
 * Contains \Drupal\widget_block\Backend\WidgetBlockBackend.
 */

namespace Drupal\widget_block\Backend;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\widget_block\Entity\WidgetBlockConfigInterface;
use Drupal\widget_block\Renderable\WidgetMarkupInterface;
use Drupal\widget_block\Utility\ResponseHelper;

/**
 * Default implementation which provides access to the Widget platform.
 */
class WidgetBlockBackend implements WidgetBlockBackendInterface {

  /**
   * Cache used for resolved widget markup.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * An HTTP client which can be used for requesting widget markup.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger channel for this backend.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Create a WidgetBlockBackend object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface
   *   Cache used for resolved widget markup.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client which can be used to requesting widget markup.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   An instance of LoggerChannelInterface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator service.
   */
  public function __construct(CacheBackendInterface $cache, ClientInterface $http_client, LoggerChannelInterface $logger, ModuleHandlerInterface $module_handler, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    // Setup object members.
    $this->cache = $cache;
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Get the widget markup cache.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   An instance of CacheBackendInterface.
   */
  protected function getCache() {
    return $this->cache;
  }

  /**
   * Get the HTTP client.
   *
   * @return \GuzzleHttp\ClientInterface
   *   An instance of ClientInterface.
   */
  protected function getHttpClient() {
    return $this->httpClient;
  }

  /**
   * Get the logger instance.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   An instance of LoggerChannelInterface.
   */
  protected function getLogger() {
    return $this->logger;
  }

  /**
   * Get the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   An instance of ModuleHandlerInterface.
   */
  protected function getModuleHandler() {
    return $this->moduleHandler;
  }

  /**
   * Get the cache tags invalidator.
   *
   * @return \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   *   An instance of CacheTagsInvalidatorInterface.
   */
  protected function getCacheTagsInvalidator() {
    return $this->cacheTagsInvalidator;
  }

  /**
   * Create a request URL for specified Widget Block configuration.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the markup should be resolved.
   *
   * @return string
   *   A widget request URL.
   */
  protected function createRequestUrl(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    // Build the request URL for given configuration.
    return "{$config->getProtocol()}://{$config->getHostname()}/widget/{$config->getIncludeMode()}/{$config->id()}?language={$language->getId()}";
  }

  /**
   * Create a request for specified Widget Block configuration.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the markup should be resolved.
   *
   * @return \Drupal\widget_block\Utility\WidgetResponse
   *   An instance of WidgetResponse.
   */
  protected function createWidgetResponse(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    // Create the request URL for given configuration.
    $url = $this->createRequestUrl($config, $language);
    // Send request and wait for response.
    $response = $this->getHttpClient()->request('GET', $url);
    // Overwrite the following headers:
    return $response
      // Depending on the Widget configuration some widgets might
      // fallback to a different language than what we requested.
      // However language code is important for our caching logic.
      // Therefor we overwrite the response language with the
      // requested language.
      ->withHeader('X-Widget-Language', $language->getId())
      // Currently the include mode is not available in the response.
      // However to provide a uniform way generating the markup and
      // handling a response we include it in the response.
      ->withHeader('X-Widget-Mode', $config->getIncludeMode());
  }

  /**
   * Determine whether markup has changed.
   *
   * @param \Drupal\widget_block\Renderable\WidgetMarkupInterface|null $current
   *   The current markup from which comparison is performed.
   * @param \Drupal\widget_block\Renderable\WidgetMarkupInterface|null $new
   *   The new markup to compare against.
   */
  protected function hasMarkupChanged(WidgetMarkupInterface $current = NULL, WidgetMarkupInterface $new = NULL) {
    // Initialize $changed variable to FALSE as default behavior. This flag will indicate whether a change
    // was detected.
    $changed = FALSE;

    if ($current !== NULL && $new !== NULL) {
      // Determine whether a new version is available.
      $changed = $current->getRefreshed() < $new->getRefreshed();
    }
    else {
      // Current or new is no longer available and should be noticed as a change.
      $changed = TRUE;
    }

    return $changed;
  }

  /**
   * Get the cache identifier based on the widget configuration.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the markup should be resolved.
   *
   * @return string
   *   A unique cache identifier for specified configuration.
   */
  protected function getCacheIdFromConfig(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    return "{$config->id()}:{$config->getIncludeMode()}:{$language->getId()}";
  }

  /**
   * Get markup from local cache.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the markup should be resolved.
   *
   * @return \Drupal\widget_block\Renderable\WidgetMarkupInterface|null
   *   An instance of WidgetMarkupInterface if resolved, otherwise NULL.
   */
  protected function getMarkupFromCache(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    // Initialize $markup to NULL as default behavior. This will hold the resolved
    // widget markup matching the specified configuration.
    $markup = NULL;
    // Get the cache identifier based on given configuration.
    $cid = $this->getCacheIdFromConfig($config, $language);
    // Get the cache markup data for given widget configuration.
    $markup_cache = $this->getCache()->get($cid);

    // Check whether the markup was resolved from cache.
    if ($markup_cache !== FALSE) {
      // Use the unserialized markup from cache.
      $markup = $markup_cache->data;
    }

    return $markup;
  }

  /**
   * Apply markup to the underlying cache.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the markup should be resolved.
   * @param \Drupal\widget_bock\Renderable\WidgetMarkupInterface|NULL $markup
   *   An instance of WidgetMarkupInterface.
   */
  protected function setMarkupToCache(WidgetBlockConfigInterface $config, LanguageInterface $language, WidgetMarkupInterface $markup = NULL) {
    // Get the cache identifier based on markup.
    $cid = $this->getCacheIdFromConfig($config, $language);

    // Invalidate cache data which are using the following Widget Block Markup. Keep in mind
    // this needs to be performed before we make any chances to the cache as it would invalidate
    // our cached markup data as well.
    $this->getCacheTagsInvalidator()->invalidateTags([
      "widget_block_markup:{$config->id()}-{$language->getId()}",
    ]);

    // Check whether the markup is cacheable.
    if ($markup !== NULL && $markup->isCacheable()) {
      // Overwrite existing cached data.
      $this->getCache()->set($cid, $markup, $markup->getCacheMaxAge(), $markup->getCacheTags());
    }
    else {
      // Remove any cached instance as markup indicates that markup is not cacheable.
      $this->getCache()->delete($cid);
    }

    // Inform other module about the cache invalidation that was performed.
    $this->getModuleHandler()->invokeAll('widget_block_invalidate', [$config, $language]);
  }

  /**
   * Get markup from the external service.
   *
   * @param \Drupal\widget_block\Entity\WidgetBlockConfigInterface $config
   *   The widget block configuration.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language for which the markup should be resolved.
   *
   * @return \Drupal\widget_block\Renderable\WidgetMarkupInterface|null
   *   An instance of WidgetMarkupInterface if resolved, otherwise NULL.
   *
   * @throws \Exception
   *   Indicates a service or response failure.
   */
  protected function getMarkupFromService(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    // Initialize $markup to NULL as default behavior. This will hold the resolved
    // widget markup matching the specified configuration.
    $markup = NULL;

    // Create widget response for given configuration.
    $response = $this->createWidgetResponse($config, $language);
    // Get the response status code.
    $status_code = $response->getStatusCode();
    // Check whether the response is successful and contains data.
    if ($status_code === 200) {
      // Create the widget block markup based on the given response.
      $markup = ResponseHelper::createMarkup($response);
    }
    // Check whether the status code is not 200 or 404 as the documentation
    // states we should only expect: 200, 404 or 500.
    elseif ($status_code !== 404) {
      // Raise exception with status message.
      throw new \RuntimeException($response->getReasonPhrase());
    }

    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    // Invalidate the cache by removing the markup.
    $this->setMarkupToCache($config, $language, NULL);
    // Log invalidation to watchdog.
    $this->getLogger()->info('Invalidated Widget Block "@id" for language "@language"', [
      '@id' => $config->id(),
      '@language' => $language->getName(),
    ]);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function refresh(WidgetBlockConfigInterface $config, LanguageInterface $language, $forced = FALSE) {
    // Initialize $refreshed variable to FALSE as default behavior. This flag will indicate whether
    // a successful refresh was performed.
    $refreshed = FALSE;

    try {
      // Get the markup from the underlying cache.
      $cached_markup = $this->getMarkupFromCache($config, $language);
      // Get the markup from the external service.
      $live_markup = $this->getMarkupFromService($config, $language);

      // Check whether the refresh should be forced or markup has changed.
      if ($forced || $this->hasMarkupChanged($cached_markup, $live_markup)) {
        // Apply markup to cache.
        $this->setMarkupToCache($config, $language, $live_markup);
        // Flag refresh as successful.
        $refreshed = TRUE;
      }
    }
    catch (\Exception $ex) {
      // Get the logger.
      $logger = $this->getLogger();
      // Log the exception message as error.
      $logger->error($ex->getMessage());
      // Log the exception stacktrace for debug purpose.
      $logger->debug("{$ex->getMessage()}:\n{$ex->getTraceAsString()}");
    }

    // Check whether markup has been refreshed.
    if ($refreshed) {
      // Log refresh to watchdog.
      $this->getLogger()->info('Refreshed Widget Block "@id" for language "@language"', [
        '@id' => $config->id(),
        '@language' => $language->getName(),
      ]);
    }

    return $refreshed;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkup(WidgetBlockConfigInterface $config, LanguageInterface $language) {
    // Try to retrieve the markup from local cache.
    $markup = $this->getMarkupFromCache($config, $language);
    // Check whether local cache does not contain any markup.
    if ($markup === NULL) {

      try {
        // Get the markup from the external service.
        $markup = $this->getMarkupFromService($config, $language);
        // Apply markup to cache.
        $this->setMarkupToCache($config, $language, $markup);
      }
      catch (\Exception $ex) {
        // Get the logger.
        $logger = $this->getLogger();
        // Log the exception message as error.
        $logger->error($ex->getMessage());
        // Log the exception stacktrace for debug purpose.
        $logger->debug("{$ex->getMessage()}:\n{$ex->getTraceAsString()}");
      }
    }

    return $markup;
  }

}
