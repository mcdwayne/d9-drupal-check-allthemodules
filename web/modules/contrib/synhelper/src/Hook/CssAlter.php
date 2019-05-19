<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * CssAlter.
 */
class CssAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$css) {
    // Embede CSS files as <link> elements.
    $system_css_preprocess = \Drupal::config('system.performance')->get('css.preprocess');
    if (!$system_css_preprocess) {
      foreach ($css as $key => $value) {
        if (strpos($value['data'], 'core/') !== 0) {
          $css[$key]['preprocess'] = FALSE;
        }
      }
    }
  }

}
