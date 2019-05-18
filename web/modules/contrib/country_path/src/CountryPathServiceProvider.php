<?php

namespace Drupal\country_path;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the language manager service.
 */
class CountryPathServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides domain_alias.validator service.
    if ($container->has('domain_alias.validator')) {
      $domain_alias_validator_definition = $container->getDefinition('domain_alias.validator');
      $domain_alias_validator_definition->setClass(CountryPathDomainAliasValidator::class);
    }
  }

}
