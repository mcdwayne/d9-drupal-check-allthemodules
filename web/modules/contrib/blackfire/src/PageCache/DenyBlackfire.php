<?php

namespace Drupal\blackfire\PageCache;

use Drupal\blackfire\EventSubscriber\BlackfireSubscriber;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Deny caching of requests during Blackfire profiling, if desired.
 */
class DenyBlackfire implements RequestPolicyInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * DenyBlackfire constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (BlackfireSubscriber::isBlackfireRequest($request)) {
      $settings = $this->config->get('blackfire.settings');
      if (!empty($settings->get('uncached'))) {
        return self::DENY;
      }
    }
    return NULL;
  }

}
