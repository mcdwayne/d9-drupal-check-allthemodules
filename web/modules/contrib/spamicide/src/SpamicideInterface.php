<?php

namespace Drupal\spamicide;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a spamicide entity type.
 */
interface SpamicideInterface extends ConfigEntityInterface {

  /**
   * Get formId method.
   */
  public function getFormId();

}
