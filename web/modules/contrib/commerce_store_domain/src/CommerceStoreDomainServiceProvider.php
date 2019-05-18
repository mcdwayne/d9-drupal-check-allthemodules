<?php

namespace Drupal\commerce_store_domain;

use Drupal\commerce_store_domain\Resolvers\StoreDomainNegotiatorResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Uses the domain negotiator resolver if the Domain module is enabled.
 */
class CommerceStoreDomainServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // We cannot use the module handler as the container is not yet compiled.
    // @see \Drupal\Core\DrupalKernel::compileContainer()
    $modules = $container->getParameter('container.modules');

    if (isset($modules['domain'])) {
      $definition = $container->getDefinition('commerce_store_domain.store_domain_resolver');
      $definition->setClass(StoreDomainNegotiatorResolver::class);
      $definition->setArguments([
        new Reference('domain.negotiator'),
        new Reference('entity_type.manager'),
      ]);
    }
  }

}
