<?php

namespace Drupal\simple_a_b_reports_google\Plugin\SimpleABReport;

use Drupal\simple_a_b\SimpleABReportingBase;

/**
 * Provides a 'GoogleAnalytics' report.
 *
 * @SimpleABReport(
 *   id = "google_analytics",
 *   name = @Translation("Google Analytics"),
 *   method = "_simple_a_b_reports_google_post_event"
 * )
 */
class GoogleAnalytics extends SimpleABReportingBase {

}
