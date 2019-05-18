<?php

namespace Drupal\config_override;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides a config override which uses the environment variable.
 * 
 * In order to use it you need the following steps:
 * - composer require symfony/dotenv
 * - Provide a .env(ironment) or sites/default/.env)ironment_ file with entries
 *   which look like:
 * @code
 * CONFIG__NAME_OF_CONFIG__KEY=overridden_VALUE
 * CONFIG__NAME2__KE2Y=overridden_VALUE2
 * @endcode
 * - If needed you can specify dynamic environment variables, for example
 *   provided by the hoster or something dynamic in settings.php.
 * - Keep in mind that you need the entry in the .env file so the pickup of the
 *   env vars is fast.
 */
class EnvironmentConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * @var string[][]
   */
  private $environmentOverrides;

  /**
   * EnvironmentConfigOverride constructor.
   *
   * @param string[][] $staticEnvironment
   *   An array of config overrides keyed config name.
   */
  public function __construct(array $staticEnvironment = []) {
    $this->environmentOverrides = $staticEnvironment;
  }


  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $config_overrides = [];
    foreach (array_intersect_key($this->environmentOverrides, array_flip($names)) as $config_name => $config) {
      foreach ($config as $config_key => $config_value) {

        $index = 'config___' . str_replace('.', '__', $config_name) . '___' . $config_key;
        if (($value = getenv($index)) && $value !== NULL) {
          $config_value = $value;
        }
        $uppercase_index = strtoupper($index);
        if (($value = getenv($uppercase_index)) && $value !== NULL) {
          $config_value = $value;
        }

        // The config value can be a json encoded object, which we will try to
        // take into account.
        $json_decode_result = json_decode($config_value, TRUE);
        if (!json_last_error()) {
          $config_value = $json_decode_result;
        }

        $config_overrides[$config_name][$config_key] = $config_value;
      }
    }
    return $config_overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'config_override__env';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    // @todo Not sure what we should define here.
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheTags(['env']);
    return $cacheableMetadata;
  }

}
