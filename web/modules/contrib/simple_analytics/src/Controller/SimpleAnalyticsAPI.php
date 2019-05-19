<?php

namespace Drupal\simple_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;

/**
 * Simple Analytics Functions.
 */
class SimpleAnalyticsAPI extends ControllerBase {

  /**
   * Get live visitors deta.
   */
  public function live() {

    $output = [];
    $output["rasult"] = ["ERROR"];
    $output["time"] = time();
    $output["visits"] = 0;
    $output["visitors"] = 0;
    $output["mobiles"] = 0;

    // Database connection.
    $con = Database::getConnection();

    // Counted from (5 minuts).
    $time_min = time() - (300);

    $query = $con->select('simple_analytics_data', 'd');
    $query->join('simple_analytics_visit', 'v', 'v.SIGNATURE = d.SIGNATURE');
    $query->fields('d', ['timestamp', 'SIGNATURE']);
    $query->fields('v', ['MOBILE', 'BOT']);
    $query->condition('d.timestamp', $time_min, ">");
    $query->condition('BOT', 0);
    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($result)) {
      // Return empty result.
      return new JsonResponse($output);
    }

    // Set Results.
    $output["rasult"] = "OK";

    // Calculate number of visits.
    $output["visits"] = count($result);

    // Calculate number of visitors.
    $signatures = [];
    foreach ($result as $item) {
      $signatures[$item['SIGNATURE']] = 1;
      if ($item['MOBILE']) {
        $output["mobiles"]++;
      }
    }
    $output["visitors"] = count($signatures);

    return new JsonResponse($output);
  }

}
