<?php

namespace Drupal\google_analytics_light_report\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GoogleAnalyticsReportCountBlock' block.
 *
 * @Block(
 *  id = "google_analytics_report_count",
 *  admin_label = @Translation("Google analytics report count for User,Seession, Bounce Rate and Pageviews"),
 * )
 */
class GoogleAnalyticsReportCountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    module_load_include('inc', 'google_analytics_light_report', 'includes/google_analytics_light_report.block');
    return google_analytics_light_report_count_content();
  }

}
