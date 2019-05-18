<?php

namespace Drupal\environmental_config\Plugin\EnvironmentDetector;

/**
 * Provides a 'EnvironmentDetector' plugin.
 *
 * @EnvironmentDetector(
 *   id = "phpenv",
 *   name = @Translation("Custom File"),
 *   description = "Tries to detect the env from a custom PHP ENV constant."
 * )
 */
class PHPEnv extends EnvPluginBase {

  /**
   * The environment name.
   */
  const ENV_VARNAME = 'ENVIRONMENTAL_CONFIG_ENV';

  /**
   * Gets the environment.
   *
   * @inheritdoc
   */
  public function getEnvironment($arg = NULL) {
    return getenv(self::ENV_VARNAME);
  }

  /**
   * Gets the weight.
   *
   * @inheritdoc
   */
  public function getWeight() {
    return -10;
  }

}
