<?php

namespace Drupal\uaparser;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use UAParser\Parser as UAParser;
use UAParser\Util\Converter;
use UAParser\Util\Fetcher;
use Psr\Log\LoggerInterface;

/**
 * A service class to integrate with ua-parser.
 */
class Parser implements ParserInterface {

  use StringTranslationTrait;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The ua-parser logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Parser object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_service
   *   The cache service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The ua-parser logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(CacheBackendInterface $cache_service, StateInterface $state, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->cache = $cache_service;
    $this->state = $state;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function update($set_message = FALSE) {
    $regexes_file = $this->configFactory->get('uaparser.settings')->get('regexes_file_location') . '/regexes.php';
    try {
      $fetcher = new Fetcher();

      // Get regexes file hash prior to update.
      if (is_file($regexes_file)) {
        $pre_hash = hash('sha512', file_get_contents($regexes_file));
      }
      else {
        $pre_hash = '';
      }

      $converter = new Converter($this->configFactory->get('uaparser.settings')->get('regexes_file_location'));
      $converter->convertString($fetcher->fetch());

      // Get regexes file hash post-update.
      if (is_file($regexes_file)) {
        $post_hash = hash('sha512', file_get_contents($regexes_file));
      }
      else {
        $this->logger->error('User-agent definitions file update failed.');
        return FALSE;
      }

      $this->state->set('uaparser.last_update', REQUEST_TIME);
      if ($post_hash != $pre_hash) {
        $this->cache->deleteAll();
        $this->logger->notice('User-agent definitions file updated.');
      }
    }
    catch (\Exception $e) {
      $this->logger->error('User-agent definitions file update failed, error: @error.', ['@error' => $e->getMessage()]);
      if ($set_message) {
        drupal_set_message($this->t('Update error: @error', ['@error' => $e->getMessage()]), 'error');
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function parse($ua, $use_cache = TRUE) {
    if ($use_cache && ($cache = $this->cache->get($ua))) {
      $result = $cache->data;
    }
    else {
      $result = [];
      try {
        $regexes_file = $this->configFactory->get('uaparser.settings')->get('regexes_file_location') . '/regexes.php';
        if (!file_exists($regexes_file)) {
          $this->update();
        }
        Timer::start('uaparser:parse');
        $parser = UAParser::create($regexes_file);
        $result['client'] = $parser->parse($ua);
        $time = Timer::stop('uaparser:parse')['time'];
        if ($use_cache) {
          $this->cache->set($ua, $result, Cache::PERMANENT, []);
        }
        $result['time'] = $time;
      }
      catch (\Exception $e) {
        $result['error'] = $e->getMessage();
      }
    }

    return $result;
  }

}
