<?php

namespace Drupal\elastic_email\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Determines access based upon valid settings.
 */
class ValidateSettings implements AccessInterface {

  /**
   * Checks access based upon valid elastic email settings.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $config = \Drupal::config('elastic_email.settings');
    $site_mail = \Drupal::config('system.site')->get('mail');
    $username = $config->get('username');
    $api_key  = $config->get('api_key');

    if (empty($site_mail)) {
      return AccessResult::forbidden();
    }
    if (empty($username)) {
      return AccessResult::forbidden();
    }
    if (empty($api_key)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}