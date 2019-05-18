<?php

namespace Drupal\blackfire\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class BlackfireSubscriber.
 *
 * @package Drupal\blackfire
 */
class BlackfireSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger for Blackfire.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagInvalidator
   *   The cache tag invalidator.
   */
  public function __construct(Connection $db, LoggerChannelInterface $logger, ConfigFactoryInterface $configFactory, CacheTagsInvalidatorInterface $cacheTagInvalidator, EntityTypeManagerInterface $entityTypeManager) {
    $this->db = $db;
    $this->logger = $logger;
    $this->configFactory = $configFactory;
    $this->cacheTagInvalidator = $cacheTagInvalidator;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onRequest'];
    return $events;
  }

  /**
   * Determine whether a request is for Blackfire profiling.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   Whether the request is for Blackfire profiling.
   */
  static public function isBlackfireRequest(Request $request) {
    return $request->headers->get('X-Blackfire-Query', NULL, TRUE);
  }

  /**
   * Log a Blackfire request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  protected function logRequest(Request $request) {
    $blackfire_header = $request->headers->get('X-Blackfire-Query', NULL, TRUE);
    if ($blackfire_header === NULL) {
      $this->logger->error('Attempting to log Blackfire request, but no Blackfire header found.');
      return;
    }

    // Find the Blackfire profile ID.
    parse_str($blackfire_header, $blackfire_settings);
    if (!isset($blackfire_settings['agentIds'])) {
      $this->logger->error('No Blackfire agentIds setting found.');
      return;
    }
    $agent_ids = explode(',', $blackfire_settings['agentIds']);
    $profile_id = NULL;
    foreach ($agent_ids as $id) {
      if (preg_match('/^request-id-([\w-]{36})$/', $id, $matches)) {
        $profile_id = $matches[1];
        break;
      }
    }
    if (empty($profile_id)) {
      $this->logger->error('No Blackfire profile ID found in agentIds %ids.', [
        '%ids' => $agent_ids,
      ]);
      return;
    }

    $title = isset($blackfire_settings['profile_title']) ?
      $blackfire_settings['profile_title'] : '';
    $method = $request->getMethod();
    $uri = $request->getUri();

    $record = [
      'profile_id' => $profile_id,
      'timestamp' => REQUEST_TIME,
      'method' => $method,
      'uri' => $uri,
      'title' => $title,
    ];
    $this->db->merge('blackfire_profiles')->key('profile_id', $profile_id)
      ->insertFields($record)->execute();
  }

  /**
   * Invalidate caches so that Blackfire can profile uncached behavior.
   */
  protected function invalidateCaches() {
    $settings = $this->configFactory->get('blackfire.settings');
    $uncached = array_keys($settings->get('uncached'));

    $tags = [];
    foreach ($uncached as $type) {
      $tags = array_merge($tags,
        $this->entityTypeManager->getDefinition($type)->getListCacheTags());
    }

    if (!empty($tags)) {
      $this->cacheTagInvalidator->invalidateTags($tags);
    }
  }

  /**
   * Respond to each request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    if (self::isBlackfireRequest($request)) {
      $this->logRequest($request);
      $this->invalidateCaches();
    }
  }

}
