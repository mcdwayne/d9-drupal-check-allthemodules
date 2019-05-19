<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\synhelper\Controller\IdnaConvert;

/**
 * PreprocessHtml.
 */
class PhpmailAlterFromAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$mail) {
    $idn = new IdnaConvert();
    $mail = trim($mail);
    $user = strstr($mail, '@', TRUE);
    $domain = strstr($mail, '@');
    $domain = substr($domain, 1);
    if (strpos($domain, '>')) {
      $domain = str_replace('>', '', $domain);
      $domain = $idn->encode($domain) . '>';
    }
    else {
      $domain = $idn->encode($domain);
    }
    $mail = "{$user}@{$domain}";
  }

}
