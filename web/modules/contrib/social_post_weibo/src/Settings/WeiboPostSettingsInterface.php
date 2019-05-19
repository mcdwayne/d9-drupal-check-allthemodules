<?php

namespace Drupal\social_post_weibo\Settings;

/**
 * Defines an interface for Social Post Weibo settings.
 */
interface WeiboPostSettingsInterface {

  /**
   * Gets the consumer key.
   *
   * @return string
   *   The consumer key.
   */
  public function getConsumerKey();

  /**
   * Gets the consumer secret.
   *
   * @return string
   *   The consumer secret.
   */
  public function getConsumerSecret();

}
