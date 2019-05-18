<?php


namespace Drupal\healthcheck\Plugin\healthcheck;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Drupal\update\UpdateManagerInterface;

/**
 * @Healthcheck(
 *  id = "updates",
 *  label = @Translation("Core and Module Updates"),
 *  description = "Checks for core and module updates.",
 *  tags = {
 *   "security",
 *  }
 * )
 */
class Updates extends HealthcheckPluginBase {

  use StringTranslationTrait;

  public function getFindings() {
    $findings = [];

    // Check if the update service exists.
    if (function_exists('update_get_available')) {
      $available = update_get_available(TRUE);
      $updates = update_calculate_project_data($available);

      // Go through each update and add a result by status.
      foreach ($updates as $update) {
        $status = !empty($update['status']) ? $update['status'] : -1;

        $key = $this->getPluginId() . '.' . $update['name'];

        $data = [
          'update_status' => $status,
          'project_name' => $update['name'],
        ];

        $placeholders = [
          ':project_name' => $update['name'],
        ];

        switch ($status) {
          case UpdateManagerInterface::NOT_SECURE:
          case UpdateManagerInterface::REVOKED:
            $finding = $this->actionRequested($key, $data);
            $finding->setLabel($this->t(
              'Insecure project :project_name',
              $placeholders
            ));

            $finding->setMessage($this->t(
              'The installed version of the project :project_name is no longer secure. Please update the project as soon as possible.',
              $placeholders
            ));
            $findings[] = $finding;
            break;

          case UpdateManagerInterface::NOT_SUPPORTED:
            $finding = $this->actionRequested($key, $data);
            $finding->setLabel($this->t(
              'Usupported module :project_name',
              $placeholders
            ));

            $finding->setMessage($this->t(
              'The installed version of the project :project_name is not supported. Please update the project as soon as possible.',
              $placeholders
            ));
            $findings[] = $finding;
            break;

          case UpdateManagerInterface::NOT_CURRENT:
            $finding = $this->needsReview($key, $data);
            $finding->setLabel($this->t(
              'Update available for :project_name',
              $placeholders
            ));

            $finding->setMessage($this->t(
              'The project :project_name has a new updated available on Drupal.org. Please update the project as soon as possible.',
              $placeholders
            ));
            $findings[] = $finding;
            break;

          case UpdateManagerInterface::CURRENT:
            $finding = $this->noActionRequired($key, $data);
            $finding->setLabel($this->t(
              'project :project_name is current',
              $placeholders
            ));

            $finding->setMessage($this->t(
              'The installed version of project :project_name is current and does not need to be updated.',
              $placeholders
            ));
            $findings[] = $finding;
            break;

          default:
            $finding = $this->notPerformed($key, $data);
            $finding->setLabel($this->t(
              'Unable to check for updates for :project_name ',
              $placeholders
            ));

            $finding->setMessage($this->t(
              'Healthcheck could not determine the update status for project :project_name.',
              $placeholders
            ));
            $findings[] = $finding;
            break;
        }
      }
    }

    // Return the results.
    return $findings;
  }

}
