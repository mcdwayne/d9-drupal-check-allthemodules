<?php


namespace Drupal\healthcheck_findings_test\Plugin\healthcheck;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;

/**
 * @Healthcheck(
 *  id = "all_findings",
 *  label = @Translation("All findings"),
 *  description = "A test module providing a finding for each status.",
 *  tags = {
 *   "testing",
 *  }
 * )
 */
class AllFindings extends HealthcheckPluginBase {

  use StringTranslationTrait;

  public function getFindings() {
    $findings = [];

    // Get a list of text constants and their status codes.
    $statuses = FindingStatus::getAsArrayByConstants();

    // Get a list of labels too.
    $labels = FindingStatus::getLabelsByConstants();

    // Create a new finding for each status code.
    foreach ($statuses as $key => $status) {
      // Create the finding with the given status and key.
      $finding = new Finding($status, $this, 'all_findings.' . $key);

      // Set the finding label.
      $finding->setLabel($this->t('Finding status @label', [
        '@label' => $labels[$key],
      ]));

      // And the message.
      $finding->setMessage($this->t('Constant finding for @status', [
        '@status' => $status,
      ]));

      // Add it to the result array.
      $findings[] = $finding;
    }

    return $findings;
  }

}
