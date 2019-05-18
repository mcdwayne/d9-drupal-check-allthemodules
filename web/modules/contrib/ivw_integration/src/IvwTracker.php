<?php

namespace Drupal\ivw_integration;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;

/**
 * Class IvwTracker.
 *
 * @package Drupal\ivw_integration
 */
class IvwTracker implements IvwTrackerInterface, CacheableDependencyInterface {

  /**
   * Static cache of tracking information.
   *
   * @var array|null
   */
  protected $trackingInformation;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token object.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The IVW lookup service.
   *
   * @var \Drupal\ivw_integration\IvwLookupServiceInterface
   */
  protected $lookupService;

  /**
   * Generates IVW tracking information.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\ivw_integration\IvwLookupServiceInterface $lookupService
   *   The IVW lookup service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Token $token,
    IvwLookupServiceInterface $lookupService
  ) {
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->lookupService = $lookupService;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackingInformation() {
    if (!isset($this->trackingInformation)) {
      $this->trackingInformation = [
        'st' => $this->getSt(),
        'mobile_st' => $this->getMobileSt(),
        'cp' => $this->getCp(),
        'sv' => $this->getSv(),
        'mobile_sv' => $this->getMobileSv(),
        'sc' => $this->getSc(),
      ];
      // Calculate cpm based upon cp.
      // TODO: this is absolutely not generic.
      $this->trackingInformation['cpm'] = str_replace('D1A', 'D2A', $this->trackingInformation['cp']);
    }
    return $this->trackingInformation;
  }

  /**
   * Gets the st parameter.
   *
   * @return string
   *   The value of the st parameter.
   */
  protected function getSt() {
    return $this->configFactory->get('ivw_integration.settings')->get('site');
  }

  /**
   * Gets the mobile_st parameter.
   *
   * @return string
   *   The value of the mobile_st parameter.
   */
  protected function getMobileSt() {
    return $this->configFactory->get('ivw_integration.settings')->get('mobile_site');
  }

  /**
   * Gets the cp parameter.
   *
   * Possible overrides have been applied for the current page.
   *
   * @return string
   *   The value of the cp parameter.
   */
  protected function getCp() {
    $settings = $this->configFactory->get('ivw_integration.settings');
    $code_template = $settings->get('code_template');

    return $this->token->replace($code_template, [], ['sanitize' => FALSE]);
  }

  /**
   * Gets the sv parameter.
   *
   * @return string
   *   The value of the sv parameter.
   *   If non is defined anywhere 'in' is returned as default.
   */
  protected function getSv() {
    $sv = $this->token->replace('[ivw:frabo]', [], ['sanitize' => FALSE]);
    return empty($sv) ? 'in' : $sv;
  }

  /**
   * Gets the sv parameter for mobile devices.
   *
   * @return string
   *   The value of the sv parameter.
   *   If non is defined anywhere 'mo' is returned as default.
   */
  protected function getMobileSv() {
    $sv = $this->token->replace('[ivw:frabo_mobile]', [], ['sanitize' => FALSE]);
    return empty($sv) ? 'mo' : $sv;
  }

  /**
   * Gets the sc parameter.
   *
   * @return string
   *   The value of the st parameter.
   */
  protected function getSc() {
    return $this->configFactory->get('ivw_integration.settings')->get('mcvd') ? 'yes' : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = $this->lookupService->getCacheTagsByCurrentRoute();

    $settings = $this->configFactory->get('ivw_integration.settings');

    return Cache::mergeTags($cache_tags, $settings->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
