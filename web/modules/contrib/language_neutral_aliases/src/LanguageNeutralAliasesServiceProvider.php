<?php

namespace Drupal\language_neutral_aliases;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the path alias storage service.
 */
class LanguageNeutralAliasesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides path.alias_storage class to store aliases language neutral.
    $definition = $container->getDefinition('path.alias_storage');
    $definition->setClass('Drupal\language_neutral_aliases\LanguageNeutralAliasesStorage');
  }

}
