<?php

namespace Drupal\google_analytics_light_report\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class GoogleAnalyticsLightReportPageUserViewYear.
 */
class GoogleAnalyticsLightReportPageReport extends ControllerBase {

  /**
   * It will return html data.
   *
   * @return html
   *   Return html output.
   */
  public function content() {

    $data = [];
    return [
      '#theme' => 'google_analytics_report_content',
      '#data' => $data,
    ];

  }

}
