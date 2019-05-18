<?php

namespace Drupal\jsnippet;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Example entity.
 */
interface JSnippetInterface extends ConfigEntityInterface {

  /**
   * Save the configuration.
   */
  public function save();

}
