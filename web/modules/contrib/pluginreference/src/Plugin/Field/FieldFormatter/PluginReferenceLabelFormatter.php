<?php

namespace Drupal\pluginreference\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'plugin_reference_label' formatter.
 *
 * @FieldFormatter(
 *   id = "plugin_reference_label",
 *   label = @Translation("Plugin Label"),
 *   field_types = {
 *     "plugin_reference"
 *   }
 * )
 */
class PluginReferenceLabelFormatter extends PluginReferenceIdFormatter {

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $plugin = \Drupal::getContainer()
      ->get('plugin.manager.' . $this->getFieldSetting('target_type'))
      ->getDefinition($item->value);
    return Html::escape($plugin['label']);
  }

}
