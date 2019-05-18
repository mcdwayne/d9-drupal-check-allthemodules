<?php

namespace Drupal\alt_login;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class AltLoginServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('basic_auth.authentication.basic_auth')
      ->setClass('Drupal\alt_login\Authentication\Provider\BasicAuth');
  }

}
