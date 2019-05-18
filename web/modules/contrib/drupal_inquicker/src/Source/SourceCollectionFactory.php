<?php

namespace Drupal\drupal_inquicker\Source;

use Drupal\drupal_inquicker\traits\CommonUtilities;
use Drupal\drupal_inquicker\traits\DependencyInjection;
use Drupal\drupal_inquicker\traits\Singleton;

/**
 * A factory to get SourceCollection objects.
 */
class SourceCollectionFactory {

  use CommonUtilities;
  use DependencyInjection;
  use Singleton;

  /**
   * Get all Sources, defined in the settings.php (see ./README.md).
   */
  public function all() : SourceCollection {
    $return = new SourceCollection();

    foreach ($this->configGetArray('sources') as $key => $source) {
      $return->add([$this->sourceFactory()->fromConfig($key, $source)]);
    }

    return $return;
  }

}
