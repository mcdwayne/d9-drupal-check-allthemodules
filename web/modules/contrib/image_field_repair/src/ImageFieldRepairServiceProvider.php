<?php

namespace Drupal\image_field_repair;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;

/**
 * Override standard widget service for fix image multiple upload.
 *
 * @package Drupal\image_field_repair
 * @see ImageFieldRepairWidgetPluginManager.
 */
class ImageFieldRepairServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (!Settings::get('image_field_repair_disable_fix_2644468')) {
      $definition = $container->getDefinition('plugin.manager.field.widget');
      $definition->setClass(ImageFieldRepairWidgetPluginManager::class);
    }
  }

}
