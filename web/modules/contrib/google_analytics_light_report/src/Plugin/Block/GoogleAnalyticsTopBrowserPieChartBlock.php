<?php

namespace Drupal\google_analytics_light_report\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GoogleAnalyticsTopBrowserPieChartBlock' block.
 *
 * @Block(
 *  id = "google_analytics_browser_pie_chart",
 *  admin_label = @Translation("Google analytics Top Browser pie chart."),
 * )
 */
class GoogleAnalyticsTopBrowserPieChartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = [];
    return [
      '#theme' => 'google_analytics_report_top_browser_pie_chart',
      '#data' => $data,
      '#attached' => [
        'library' => ['google_analytics_light_report/google_analytics_report_top_browser_pie_chart'],
      ],
    ];
  }

}
