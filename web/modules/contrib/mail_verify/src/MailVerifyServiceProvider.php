<?php

namespace Drupal\mail_verify;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

class MailverifyServiceProvider implements ServiceProviderInterface {
  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Override the validation service.
    $definition = $container->getDefinition('email.validator');
    if ($definition->getClass() == \Egulias\EmailValidator\EmailValidator::class) {
      $mail_verify = $container->getDefinition('mail_verify.email.validator');
      $definition->setClass($mail_verify->getClass());
      $definition->setArguments($mail_verify->getArguments());
    }
  }
}
