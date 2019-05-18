<?php

namespace Drupal\drupal_inquicker\Source;

use Drupal\drupal_inquicker\traits\CommonUtilities;
use Drupal\drupal_inquicker\traits\DependencyInjection;
use Drupal\drupal_inquicker\traits\Singleton;

/**
 * A factory to get Source objects.
 */
class SourceFactory {

  use CommonUtilities;
  use DependencyInjection;
  use Singleton;

  /**
   * Create a source from an entry in the config.
   *
   * @param string $key
   *   The key such as default or dummy.
   * @param array $config
   *   The config, which will contain things like the api key.
   *
   * @return Source
   *   An inquicker source.
   */
  public function fromConfig(string $key, array $config) : Source {
    try {
      if (!$config['source']) {
        throw new \Exception('inquicker configuration source must be set, see ./README.md for details.');
      }
      switch ($config['source']) {
        case 'class':
          return new $config['class']($key, $config);

        case 'live':
          return new LiveSource($key, $config);

        default:
          throw new \Exception('inquicker configuration soure not in "live", "class"');
      }
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return new InvalidSource($key, $config, 'Configuration source must be set, see ./README.md');
    }
  }

}
