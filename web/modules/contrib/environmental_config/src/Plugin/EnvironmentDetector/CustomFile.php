<?php

namespace Drupal\environmental_config\Plugin\EnvironmentDetector;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Site\Settings;

/**
 * Provides a 'EnvironmentDetector' plugin.
 *
 * @EnvironmentDetector(
 *   id = "customfile",
 *   name = @Translation("Server Name Custom File"),
 *   description = "Tries to detect the env matching URI and a custom file."
 * )
 */
class CustomFile extends EnvPluginBase {

  /**
   * Gets the environment.
   *
   * @inheritdoc
   */
  public function getEnvironment($arg = NULL) {
    global $base_url;

    if (NULL === $arg) {
      $arg = str_ireplace('https://', '', $base_url);
      $arg = str_ireplace('http://', '', $arg);
    }

    $settings = Settings::get('environmental_config', []);
    $path = isset($settings['plugins']) && isset($settings['plugins']['customfile']) &&
            isset($settings['plugins']['customfile']['environments_file_path']) ? $settings['plugins']['customfile']['environments_file_path'] : NULL;

    if ($path && file_exists($path)) {
      $yml = $this->getDecodedYml($this->getFileContent($path)) ?: [];
      if (isset($yml[$arg]) && isset($yml[$arg]['env'])) {
        return $yml[$arg]['env'];
      }
    }
    return FALSE;
  }

  /**
   * Gets the weight.
   *
   * @inheritdoc
   */
  public function getWeight() {
    return -11;
  }

  /**
   * Gets decoded YML in an array format.
   *
   * @param string $data
   *   The raw data from a YML source.
   *
   * @return array
   *   The decoded YML.
   */
  protected function getDecodedYml($data) {
    return Yaml::decode($data);
  }

  /**
   * Gets the file content.
   *
   * @param string $path
   *   The path.
   *
   * @return string|false
   *   The content of the file.
   */
  protected function getFileContent($path) {
    return file_get_contents($path);
  }

}
