<?php

namespace Drupal\config_share\Plugin\FeaturesAssignment;

use Drupal\config_share\Plugin\ConfigProvider\ConfigProviderShare;
use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning shared configuration to the
 * InstallStorage::CONFIG_OPTIONAL_DIRECTORY.
 *
 * @Plugin(
 *   id = "shared",
 *   weight = 20,
 *   name = @Translation("Shared"),
 *   description = @Translation("Assign shared configuration to the 'config/shared' install directory."),
 * )
 */
class ConfigSharedType extends FeaturesAssignmentMethodBase {
  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $current_bundle = $this->assigner->getBundle();

    $config_collection = $this->featuresManager->getConfigCollection();

    foreach ($config_collection as &$item) {
      if ($current_bundle->getShortName($item->getPackage()) == 'core') {
        $item->setSubdirectory(ConfigProviderShare::ID);
      }
    }
    // Clean up the $item pass by reference.
    unset($item);

    $this->featuresManager->setConfigCollection($config_collection);
  }

}
