<?php

namespace Drupal\devel_mode;

/**
 * Provide interface for ConfigProvider.
 */
interface ConfigProviderInterface {

  /**
   * Provide configs.
   */
  public function getConfigs();

}
