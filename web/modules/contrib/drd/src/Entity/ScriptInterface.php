<?php

namespace Drupal\drd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Script entities.
 */
interface ScriptInterface extends ConfigEntityInterface {

  /**
   * Get the scripts type.
   *
   * @return string
   *   The script type.
   */
  public function type();

  /**
   * Get the script code.
   *
   * @return string
   *   The script code.
   */
  public function code();

  /**
   * Execute the script.
   *
   * @param array $arguments
   *   Script arguments.
   * @param string $workingDir
   *   Working directory for the script.
   *
   * @throws \Exception
   */
  public function execute(array $arguments, $workingDir);

  /**
   * Get the script output.
   *
   * @return string
   *   The script output.
   */
  public function getOutput();

}
