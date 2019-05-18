<?php

namespace Drupal\form_alter_service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\form_alter_service\Form\FormAlter;
use Drupal\form_alter_service\Form\FormBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Service provider.
 */
class FormAlterServiceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container
      ->setDefinition(FormAlter::SERVICE_ID, new Definition(FormAlter::class));

    $container
      ->addCompilerPass(new FormAlterCompilerPass());
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('form_builder')
      ->setClass(FormBuilder::class);
  }

}
