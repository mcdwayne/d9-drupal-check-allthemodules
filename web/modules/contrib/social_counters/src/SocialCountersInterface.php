<?php
/**
 * @file
 * Provides Drupal\social_counters\SocialCountersInterface.
 */

namespace Drupal\social_counters;

/**
 * An interface for all Social Counters type plugins.
 */
interface SocialCountersInterface {
  /**
   * Get number of followers / subscribers / likes / etc. for social network.
   */
  public function getCount();

  /**
   * Label for Social network.
   */
  public function label();

  /**
   * Add additional fields to the Social Counters Entity form.
   */
  public function entityForm(&$form, &$form_state, $config);
}
