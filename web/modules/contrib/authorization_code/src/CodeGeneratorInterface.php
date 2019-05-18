<?php

namespace Drupal\authorization_code;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * The code generator plugin interface.
 */
interface CodeGeneratorInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  const DEFAULT_CODE_LENGTH = 4;

  /**
   * Generates random code.
   *
   * @return string
   *   The generated code.
   */
  public function generate(): string;

}
