<?php

namespace Drupal\image_field_repair;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\image_field_repair\Plugin\Field\FieldWidget\ImageFieldRepairWidget;

/**
 * Plugin manager for decorate ImageWidget.
 *
 * @ingroup image_field_repair
 */
class ImageFieldRepairWidgetPluginManager extends WidgetPluginManager {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if ($plugin_id === 'image_image') {
      $plugin_definition = $this->getDefinition($plugin_id);
      $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
      if ($plugin_class === ImageWidget::class) {
        $plugin_class = ImageFieldRepairWidget::class;
        $plugin_definition['class'] = $plugin_class;
        return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
      }
    }

    return parent::createInstance($plugin_id, $configuration);
  }

}
