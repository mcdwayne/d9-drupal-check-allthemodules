<?php

namespace Drupal\module_builder_devel;

use Drupal\module_builder\DrupalCodeBuilder;
use Drupal\module_builder_devel\Environment\ModuleBuilderDevel;

/**
 * Replacement library wrapper service, to switch the environment.
 */
class DrupalCodeBuilderDevel extends DrupalCodeBuilder {

  /**
   * {@inheritdoc}
   */
  protected function doLoadLibrary() {
    $environment = new ModuleBuilderDevel;

    \DrupalCodeBuilder\Factory::setEnvironment($environment)
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

}