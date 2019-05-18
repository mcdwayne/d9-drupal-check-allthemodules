<?php

namespace Drupal\local_translation_content;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\local_translation_content\Access\LocalTranslationContentManageAccess;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class LocalTranslationContentServiceProvider.
 *
 * @package Drupal\local_translation_content
 */
class LocalTranslationContentServiceProvider extends ServiceProviderBase {

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
      new Reference('plugin_manager.local_translation_content_access_rules'),
    ];
    foreach ($access_services as $service) {
      $container->getDefinition($service)
        ->setClass(LocalTranslationContentManageAccess::class)
        ->setArguments($arguments);
    }
  }

}
