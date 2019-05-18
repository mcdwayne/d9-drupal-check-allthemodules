<?php

namespace Drupal\cision_notify_pull;

/**
 * Interface CisionManager.
 *
 * @package Drupal\cision_notify_pull
 */
interface CisionManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function processCisionFeeds($xml);

  /**
   * {@inheritdoc}
   */
  public function deleteCisionFeeds($xml);

}
