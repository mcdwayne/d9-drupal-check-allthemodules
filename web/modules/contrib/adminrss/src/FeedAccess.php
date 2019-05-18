<?php

namespace Drupal\adminrss;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Class FeedAccess provides access checking to feed routes.
 */
class FeedAccess implements AccessInterface {

  /**
   * The access token.
   *
   * @var string
   */
  protected $token;

  /**
   * FeedAccess constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->token = $configFactory->get(AdminRss::CONFIG)->get(AdminRss::TOKEN);
  }

  /**
   * Check access control to AdminRSS feeds.
   *
   * @param string $adminrss_token
   *   The access token.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result for the feed.
   */
  public function access($adminrss_token) {
    $access = ($adminrss_token === $this->token)
      ? AccessResult::allowed()
      : AccessResult::forbidden('Invalid token');

    return $access;
  }

}
