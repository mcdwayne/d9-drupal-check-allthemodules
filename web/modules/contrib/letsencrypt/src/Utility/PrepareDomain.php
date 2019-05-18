<?php

namespace Drupal\letsencrypt\Utility;

/**
 * Prepare.
 */
class PrepareDomain {

  /**
   * Hook.
   */
  public static function init($data, $decode = FALSE) {
    $data = str_replace([",", " "], "\n", $data);
    $domains = [];
    foreach (explode("\n", $data) as $value) {
      $domain = trim($value);
      if (strlen($domain) > 3) {
        if ($decode) {
          $domain = \Drupal::service('idna')->decode($domain);
        }
        else {
          $domain = \Drupal::service('idna')->encode($domain);
        }
        if (!in_array($domain, $domains)) {
          $domains[] = $domain;
        }
      }
    }
    return $domains;
  }

}
