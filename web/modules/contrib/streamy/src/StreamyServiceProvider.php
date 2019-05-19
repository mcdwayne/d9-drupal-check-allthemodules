<?php

namespace Drupal\streamy;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\Site\Settings;

/**
 * Streamy dependency injection container.
 */
class StreamyServiceProvider implements ServiceProviderInterface {

  /**
   * Scheme names that shouldn't be registered as a service.
   */
  const UNTOUCHABLE_SCHEMES = ['streamy', 'streamypvt'];

  /**
   * Registers a service based on the scheme name declared in the settings.php file.
   * To avoid service names collisions it will check if the actual scheme name is
   * blacklisted avoiding to re-declare the two schemes coming by default with streamy.
   *
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    foreach (Settings::get('streamy', []) as $scheme => $configuration) {
      if (!$this->schemeIsBlackListed($scheme) && is_array($configuration)) {
        $container->register('streamy.' . $scheme . '.stream_wrapper', 'Drupal\streamy\StreamWrapper\DrupalFlySystemStreamWrapper')
                  ->addTag('stream_wrapper', ['scheme' => $scheme]);
      }
    }
  }

  /**
   * Checks if a given scheme is blacklisted.
   *
   * @param $scheme
   * @return bool
   */
  private function schemeIsBlackListed($scheme) {
    return (in_array($scheme, self::UNTOUCHABLE_SCHEMES));
  }

}
