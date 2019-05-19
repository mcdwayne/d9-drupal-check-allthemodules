<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

use Drupal\Core\Field\FieldItemList;
use Drupal\Component\Utility\NestedArray;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin to render a field with a specific formatter.
 *
 * @TwigPlugin(
 *   id = "twig_extender_field_formatter",
 *   label = @Translation("Get a formatted field"),
 *   type = "filter",
 *   name = "view",
 *   function = "getField"
 * )
 */
class FieldFormatter extends TwigPluginBase {

  /**
   * Implementation for render field formatter.
   *
   * @param \Drupal\Core\Field\FieldItemList $field
   *   FieldItemList to display with formatter.
   * @param string $formatter
   *   Formatter plugin id to use.
   * @param string $label
   *   Label option for formatter, could be hidden|above|inside.
   * @param array $settings
   *   Settings for formatter plugin.
   * @param int $maxItems
   *   How many items to display.
   * @param int $offset
   *   Offset to start from.
   *
   * @return array
   *   Array for rendering.
   */
  public function getField(FieldItemList $field, $formatter, $label = 'hidden', array $settings = [], $maxItems = -1, $offset = 0) {
    $formatterService = \Drupal::service('plugin.manager.field.formatter');
    try {
      $plugin = $formatterService->getDefinition($formatter);
      $field_type = $field->getFieldDefinition()->getType();
      $field_options = $formatterService->getOptions();
      $default_settings = $formatterService->getDefaultsettings($formatter);
      $settings = NestedArray::mergeDeep($default_settings, $settings);
      $renderer = \Drupal::service('renderer');

      if ($offset > 0) {
        if (!$field->offsetExists($offset)) {
          $build = [];
          $renderer->addCacheableDependency($build, $field->getEntity());
          return $build;
        }
        for ($i = 0; $i < $offset; $i++) {
          $field->removeItem(0);
        }
      }

      if ($maxItems > 0 && $maxItems != $field->count()) {
        $remove = ($field->count() - $maxItems);
        for ($i = 0; $i < $remove; $i++) {
          $field->removeItem(1);
        }
      }

      $field_to_view = $field->view([
        'type' => $formatter,
        'settings' => $settings,
        'label' => $label,
      ]
      );
      return $field_to_view;
    }
    catch (\Exception $e) {
      \Drupal::logger('twig_extender_extras')->error($e->getMessage());
      throw $e;
    }
  }

}
