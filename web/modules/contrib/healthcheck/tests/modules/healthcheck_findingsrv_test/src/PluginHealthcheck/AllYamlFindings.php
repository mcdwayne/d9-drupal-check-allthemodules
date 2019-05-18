<?php

namespace Drupal\healthcheck_findingsrv_test\Plugin\healthcheck;

use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;

/**
 * @Healthcheck(
 *  id = "all_yaml_findings",
 *  label = @Translation("All YAML findings"),
 *  description = "A test module providing a finding for each status using YAML for the label and message.",
 *  tags = {
 *   "testing",
 *  }
 * )
 */
class AllYamlFindings extends HealthcheckPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    // Get a list of text constants and their status codes.
    $statuses = FindingStatus::getAsArrayByConstants();

    // Create a new finding for each status code.
    foreach ($statuses as $key => $status) {
      // Create the finding with the given status and key.
      $findings[] = $this->found($status, 'all_yaml_findings.' . $key, [
        'status' => $key
      ]);
    }

    return $findings;
  }

}
