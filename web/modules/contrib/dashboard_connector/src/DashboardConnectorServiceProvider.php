<?php

namespace Drupal\dashboard_connector;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Shim in the PHP version into the container.
 */
class DashboardConnectorServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->setParameter('php_version', $this->getVersion());
  }

  /**
   * Gets the current PHP Version.
   */
  protected function getVersion() {
    // Ensure we have all the defines we're looking for, even if running
    // on a PHP from the stone age.
    if (!defined('PHP_VERSION_ID')) {
      $version = explode('.', PHP_VERSION);
      return ($version[0] * 10000 + $version[1] * 100 + $version[2]);
    }
    return PHP_VERSION_ID;
  }

}
