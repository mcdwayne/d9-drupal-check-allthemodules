<?php

namespace Drupal\commander\Contracts;

/**
 * Interface CommandInterface.
 */
interface CommandInterface {

  /**
   * Command handler plugin ID.
   *
   * @return string
   *   Plugin ID.
   */
  public function handlerPluginId();

}
