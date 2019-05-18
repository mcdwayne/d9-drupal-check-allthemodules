<?php

namespace Drupal\last_tweets\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManager;
use Drupal\last_tweets\Gateway\LastTweetsGateway;

/**
 * Class LastTweetsManager.
 *
 * @package Drupal\last_tweets\Service
 */
class LastTweetsManager {

  const DEFAULTTWEETLIMIT = 3;

  /**
   * LastTweets gateway.
   *
   * @var \Drupal\last_tweets\Gateway\LastTweetsGateway
   */
  protected $lastTweetsGateway;

  /**
   * NormalizeTweets manager.
   *
   * @var \Drupal\last_tweets\Service\NormalizeTweetsManager
   */
  protected $normalizeTweetsManager;

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Language id.
   *
   * @var string
   */
  protected $languageId;

  /**
   * Default language.
   *
   * @var string
   */
  protected $defaultLanguageId;

  /**
   * Default config.
   *
   * @var array|mixed|null
   */
  protected $useDefaultConfigForAllLanguages;

  /**
   * User name.
   *
   * @var array|mixed|null
   */
  protected $userName;

  /**
   * Consumer key.
   *
   * @var array|mixed|null
   */
  protected $consumerKey;

  /**
   * Secret key.
   *
   * @var array|mixed|null
   */
  protected $secretKey;

  /**
   * Access token.
   *
   * @var array|mixed|null
   */
  protected $accessToken;

  /**
   * Access token.
   *
   * @var array|mixed|null
   */
  protected $accessTokenSecret;

  /**
   * LastTweetsService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager.
   * @param \Drupal\last_tweets\Gateway\LastTweetsGateway $lastTweetsGateway
   *   LastTweets gateway.
   * @param \Drupal\last_tweets\Service\NormalizeTweetsManager $normalizeTweetsManager
   *   NormalizeTwetts manager.
   */
  public function __construct(
    ConfigFactory $config,
    LanguageManager $languageManager,
    LastTweetsGateway $lastTweetsGateway,
    NormalizeTweetsManager $normalizeTweetsManager
  ) {
    $this->config = $config->getEditable('last_tweets.settings');
    $this->languageManager = $languageManager;
    $this->lastTweetsGateway = $lastTweetsGateway;
    $this->normalizeTweetsManager = $normalizeTweetsManager;

    $this->settings['language_id'] = ($this->config->get('use_for_all_' . $this->defaultLanguageId)) ? $this->languageManager->getDefaultLanguage()
      ->getId() : $this->languageManager->getCurrentLanguage()->getId();
    $this->settings['twitter_username'] = $this->config->get('twitter_username_' . $this->settings['language_id']);
    $this->settings['consumer_key'] = $this->config->get('consumer_key_' . $this->settings['language_id']);
    $this->settings['secret_key'] = $this->config->get('secret_key_' . $this->settings['language_id']);
    $this->settings['access_token'] = $this->config->get('access_token_' . $this->settings['language_id']);
    $this->settings['access_token_secret'] = $this->config->get('access_token_secret_' . $this->settings['language_id']);
  }

  /**
   * Get tweets.
   *
   * @param int $limit
   *   Tweets limit.
   *
   * @param $username
   *
   * @return array
   *   Normalized tweets.
   */
  public function getTweets($username, $limit = self::DEFAULTTWEETLIMIT) {
    $this->settings['twitter_username'] = $username ?: $this->settings['twitter_username'];
    $tweets = $this->lastTweetsGateway->getLastTweets($limit, $this->settings);
    return $tweets ? $this->normalizeTweetsManager->normalize($tweets) : NULL;
  }

}
