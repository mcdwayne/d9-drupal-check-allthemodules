<?php

namespace Drupal\dcat_import\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining DCAT source entities.
 */
interface DcatSourceInterface extends ConfigEntityInterface {

  /**
   * Create/update the different migrate configurations.
   */
  public function saveMigrations();

}
