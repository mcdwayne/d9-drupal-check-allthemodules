<?php

namespace Drupal\translators_content;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\translators_content\Access\TranslatorsContentManageAccess;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class TranslatorsContentServiceProvider.
 *
 * @package Drupal\translators_content
 */
class TranslatorsContentServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Replace access manager services classes with our own.
    static $access_services = [
      'content_translation.manage_access',
      'content_translation.overview_access',
      'content_translation.delete_access',
    ];
    $arguments = [
      new Reference('entity.manager'),
      new Reference('language_manager'),
      new Reference('current_user'),
      new Reference('plugin_manager.translators_content_access_rules'),
    ];
    foreach ($access_services as $service) {
      $container->getDefinition($service)
        ->setClass(TranslatorsContentManageAccess::class)
        ->setArguments($arguments);
    }
  }

}
