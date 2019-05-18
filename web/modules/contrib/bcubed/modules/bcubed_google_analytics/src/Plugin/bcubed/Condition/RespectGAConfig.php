<?php

namespace Drupal\bcubed_google_analytics\Plugin\bcubed\Condition;

use Drupal\bcubed\ConditionBase;
use Drupal\Component\Utility\Unicode;

/**
 * Restrict condition set to running on pages which GA has also been configured to run on.
 *
 * @Condition(
 *   id = "respect_ga_config",
 *   label = @Translation("Google Analytics Configured Pages Only"),
 *   description = @Translation("Run only on pages which google analytics is configured to report on"),
 *   bcubed_dependencies = {
 *    {
 *      "plugin_type" = "action",
 *      "plugin_id" = "google_analytics_event",
 *      "same_set" = true,
 *      "dependency_type" = "requires",
 *    }
 *  }
 * )
 */
class RespectGAConfig extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function preCondition() {
    $config = \Drupal::config('google_analytics.settings');
    $visibility_request_path_mode = $config->get('visibility.request_path_mode');
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    // Match path if necessary.
    if (!empty($visibility_request_path_pages)) {
      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $pages = Unicode::strtolower($visibility_request_path_pages);
      if ($visibility_request_path_mode < 2) {
        // Compare the lowercase path alias (if any) and internal path.
        $path = \Drupal::service('path.current')->getPath();
        $path_alias = Unicode::strtolower(\Drupal::service('path.alias_manager')->getAliasByPath($path));
        $page_match = \Drupal::service('path.matcher')->matchPath($path_alias, $pages) || (($path != $path_alias) && \Drupal::service('path.matcher')->matchPath($path, $pages));
        // When $visibility_request_path_mode has a value of 0, the tracking
        // code is displayed on all pages except those listed in $pages. When
        // set to 1, it is displayed only on those pages listed in $pages.
        $page_match = !($visibility_request_path_mode xor $page_match);
      }
      elseif (\Drupal::moduleHandler()->moduleExists('php')) {
        $page_match = php_eval($visibility_request_path_pages);
      }
      else {
        $page_match = FALSE;
      }
    }
    else {
      $page_match = TRUE;
    }
    return $page_match;
  }

}
