<?php

namespace Drupal\domain_lang;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\domain_lang\Language\LanguageNegotiator;

/**
 * Overrides the form_error_handler service to enable inline form errors.
 */
class DomainLangServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('language_negotiator')
      ->setClass(LanguageNegotiator::class);
  }

}
