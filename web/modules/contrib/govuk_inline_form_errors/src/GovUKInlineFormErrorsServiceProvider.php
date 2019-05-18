<?php

namespace Drupal\govuk_inline_form_errors;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the form_error_handler service to enable inline form errors.
 */
class GovUKInlineFormErrorsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('form_error_handler')
      ->setClass(GovUKFormErrorHandler::class)
      ->setArguments([
        new Reference('string_translation'),
        new Reference('link_generator'),
        new Reference('renderer'),
        new Reference('messenger'),
      ]);
  }

}
