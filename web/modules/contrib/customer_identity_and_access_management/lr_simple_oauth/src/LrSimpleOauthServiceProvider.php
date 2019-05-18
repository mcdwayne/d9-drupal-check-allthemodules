<?php

namespace Drupal\lr_simple_oauth;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the user simple_oauth.repositories.user.
 */
class LrSimpleOauthServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides argument of the UserRepository class from the simple_oauth
    // module to provide a different authentication service. 
    $definition = $container->getDefinition('simple_oauth.repositories.user');
    $definition->replaceArgument(0, new Reference('lr_simple_oauth.auth'));
  }
}
