<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;

/**
 * @Healthcheck(
 *  id = "module_folders",
 *  label = @Translation("Modules directory structure"),
 *  description = "Checks if the modules directory is structured properly.",
 *  tags = {
 *   "site code",
 *  }
 * )
 */
class ModuleFolders extends HealthcheckPluginBase {

  use StringTranslationTrait;

  public function getFindings() {
    $findings = [];

    if (is_dir(DRUPAL_ROOT . '/modules/contrib')) {
      $findings[] = $this->noActionRequired('module_folders.contrib');
    }
    else {
      $findings[] = $this->actionRequested('module_folders.contrib');
    }

    if (is_dir(DRUPAL_ROOT . '/modules/custom')) {
      $findings[] = $this->noActionRequired('module_folders.custom');
    }
    else {
      $findings[] = $this->actionRequested('module_folders.custom');
    }

    return $findings;
  }

}
