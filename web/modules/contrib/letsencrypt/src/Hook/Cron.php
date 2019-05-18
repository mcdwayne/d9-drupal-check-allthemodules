<?php

namespace Drupal\letsencrypt\Hook;

use Drupal\letsencrypt\Utility\PrepareDomain;

/**
 * Hook Cron.
 */
class Cron {

  /**
   * Hook.
   */
  public static function hook() {
    $config = \Drupal::config('letsencrypt.settings');
    if ($config->get('cron')) {
      $base = $config->get('domain-base');
      $cert = \Drupal::service('letsencrypt')->cert($base);
      $fullchain = $cert['fullchain_certificate'];
      if (file_exists($fullchain) && file_exists($cert['expire'])) {
        $d = $config->get('domain-domains');
        $domains = PrepareDomain::init($d, TRUE);
        $date = file_get_contents($cert['expire']);
        $time = strtotime($date);
        $diff = round(abs($time - time()) / 60 / 60 / 24);
        if ($diff < 20) {
          \Drupal::logger('letsencrypt')->notice("Update cert $base");
          \Drupal::service('letsencrypt')->sign($base, $domains);
        }
      }
    }
  }

}
