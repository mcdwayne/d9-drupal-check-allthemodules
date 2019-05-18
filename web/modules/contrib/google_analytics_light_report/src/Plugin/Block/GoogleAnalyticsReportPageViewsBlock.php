<?php

namespace Drupal\google_analytics_light_report\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GoogleAnalyticsReportPageViewsBlock' block.
 *
 * @Block(
 *  id = "google_analytics_report_page_views",
 *  admin_label = @Translation("Google analytics report for Pageviews List."),
 * )
 */
class GoogleAnalyticsReportPageViewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'google_analytics_light_report', 'includes/google_analytics_light_report.block');
    return google_analytics_light_report_page_views_content();
  }

}
