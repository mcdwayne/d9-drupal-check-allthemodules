<?php

namespace Drupal\domain_language;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class DomainLanguageServiceProvider
 * @package Drupal\domain_language
 */
class DomainLanguageServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('language.default');
    $definition->setClass(LanguageDefault::class);
  }

}
