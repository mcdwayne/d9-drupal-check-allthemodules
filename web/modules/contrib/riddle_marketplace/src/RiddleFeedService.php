<?php

namespace Drupal\riddle_marketplace;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\riddle_marketplace\Exception\NoApiKeyException;
use GuzzleHttp\Client;

/**
 * Class RiddleFeedService.
 *
 * @package Drupal\riddle_marketplace
 */
class RiddleFeedService implements RiddleFeedServiceInterface {

  /**
   * Cache Service to store Riddle Feed.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cacheService;

  /**
   * Riddle Marketplace Module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $moduleSettings;

  /**
   * Cache validity period.
   *
   * Mainly used to reduce number of requests to Riddle
   * and to keep fast response for user.
   *
   * - period has to be valid for DrupalDateTime::modify method
   * - period should be less then time required to add new Riddle entry,
   *   so that client after adding entry in Riddle can find it in search here.
   *
   * @var string
   */
  private static $cachePeriod = '30 seconds';

  /**
   * Generic title used for Riddles without defined title.
   *
   * -> Riddle UID will be appended at end of it.
   *
   * @var string
   */
  private $emptyTitlePrefix;

  /**
   * Riddle Feed Service.
   *
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheService
   *   Cache service created for caching of Riddle Feed.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configService
   *   Configuration Factory.
   */
  public function __construct(CacheBackendInterface $cacheService, ConfigFactoryInterface $configService) {
    $this->cacheService = $cacheService;

    // Fetch and store settings for this module.
    $this->moduleSettings = $configService->get('riddle_marketplace.settings');

    // Set Empty Title Prefix.
    $this->emptyTitlePrefix = $this->moduleSettings->get('riddle_marketplace.empty_title_prefix');
  }

  /**
   * {@inheritdoc}
   */
  public function getFeed() {
    $token = $this->getToken();
    $cacheId = 'riddle_marketplace.feed:' . $token;

    if ($cache = $this->cacheService->get($cacheId)) {
      $feed = $cache->data;
    }
    else {
      $riddleResponse = $this->fetchRiddleResponse();
      $feed = $this->processRiddleResponse($riddleResponse);
      $cacheExpire = $this->getCacheExpireTimestamp();

      $this->cacheService->set($cacheId, $feed, $cacheExpire);
    }

    return $feed;
  }

  /**
   * Get Riddle Token from riddle_marketplace settings.
   *
   * @return string
   *   Return defined Riddle Token.
   */
  private function getToken() {
    return $this->moduleSettings->get('riddle_marketplace.token');
  }

  /**
   * Get Riddle API Url.
   *
   * Read Url from riddle_marketplace settings and replace defined token.
   *
   * @return string
   *   Riddle API Url.
   *
   * @throws \Drupal\riddle_marketplace\Exception\NoApiKeyException
   */
  private function getApiUrl() {

    if ($token = $this->getToken()) {
      $url = str_replace(
        ["%%TOKEN%%"],
        [$token],
        $this->moduleSettings->get('riddle_marketplace.api_url')
      );
      return $url;
    }
    throw new NoApiKeyException();
  }

  /**
   * Fetch feed from Riddle API and return in JSON format (array)
   *
   * @return array
   *   JSON decoded Riddle API result.
   */
  private function fetchRiddleResponse() {
    $url = $this->getApiUrl();

    $client = new Client();
    $result = $client->request('GET', $url);

    // Return response from Riddle.
    return json_decode($result->getBody(), TRUE);
  }

  /**
   * Process response from Riddle API.
   *
   * Response is in JSON format. Method will return only relevant data
   * for internal feed cached storage.
   * - currently: uid, title.
   *
   * @param array|null $riddleResponse
   *   JSON or NULL as Riddle API Result.
   *
   * @return array
   *   Filtered Riddle Feed with params relevant for Module.
   */
  private function processRiddleResponse($riddleResponse) {
    $feed = [];

    if (!empty($riddleResponse) && is_array($riddleResponse['items'])) {
      foreach ($riddleResponse['items'] as $riddleEntry) {
        // Skip invalid riddle feed entries.
        if (!$this->isValidRiddleFeedEntry($riddleEntry)) {
          continue;
        }

        $feed[$riddleEntry['id']] = [
          'title' => $this->getRiddleTitle($riddleEntry),
          'id' => $riddleEntry['id'],
          // All riddles in response are published atm.
          'status' => 1,
          'image' => $riddleEntry['thumb'],
        ];
      }
    }

    return $feed;
  }

  /**
   * Validation Riddle Feed Entry.
   *
   * @param array|null $riddleEntry
   *   Single Riddle Feed Entry.
   *
   * @return bool
   *   Result of validation.
   */
  private function isValidRiddleFeedEntry($riddleEntry) {
    if (
      empty($riddleEntry) || !is_array($riddleEntry) || empty($riddleEntry['id'])
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns Riddle Title.
   *
   * From feed entry return Title
   * - in case title is not defined use generic name.
   *
   * @param array $riddleEntry
   *   Single Riddle Feed Entry.
   *
   * @return string
   *   Riddle element title.
   */
  private function getRiddleTitle(array $riddleEntry) {
    if (!empty($riddleEntry['title'])) {
      return $riddleEntry['title'];
    }
    return $this->emptyTitlePrefix . $riddleEntry['id'];
  }

  /**
   * Get cache validity end timestamp.
   *
   * @return int
   *   Timestamp of expire time for Cache.
   */
  private function getCacheExpireTimestamp() {
    /* @var \DateTime $date */
    $date = new DrupalDateTime();
    $date->modify('+' . static::$cachePeriod);

    return $date->getTimestamp();
  }

}
