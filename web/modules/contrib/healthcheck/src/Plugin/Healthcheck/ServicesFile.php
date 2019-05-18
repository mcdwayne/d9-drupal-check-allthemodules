<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;

/**
 * @Healthcheck(
 *  id = "services_file",
 *  label = @Translation("services.yml File"),
 *  description = "Checks for use of a custom services.yml.",
 *  tags = {
 *   "site code",
 *  }
 * )
 */
class ServicesFile extends HealthcheckPluginBase {

  use StringTranslationTrait;

  public function getFindings() {
    $findings = [];

    $services_file_path = DRUPAL_ROOT . '/sites/default/services.yml';

    if (is_file($services_file_path)) {
      $findings[] = $this->needsReview('services_file.exists');

      if (is_link($services_file_path)) {
        $findings[] = $this->critical('services_file.symlink');
      }
      else {
        $findings[] = $this->noActionRequired('services_file.symlink');
      }
    }
    else {
      $findings[] = $this->noActionRequired('services_file.exists');
    }

    return $findings;
  }

}
