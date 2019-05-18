<?php

namespace Drupal\insert\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Gets the field instance widget's Insert module specific settings.
 *
 * @MigrateProcessPlugin(
 *   id = "field_instance_widget_insert_settings",
 *   handle_multiples = TRUE
 * )
 */
class FieldInstanceWidgetInsertSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->getInsertSettings($value);
  }

  /**
   * Merges the default D8 and specified D7 Insert module settings for a widget
   * type.
   *
   * @param array $widget_settings
   *   The widget settings from D7 for this widget.
   *
   * @return array[]
   */
  public function getInsertSettings(array $widget_settings) {
    if (!isset($widget_settings['insert'])) {
      return [];
    }

    $styles = [];

    // While Insert features a dedicated "enabled" checkbox
    // ($widget_settings['insert']) in D7, Insert is enabled whenever one or
    // more styles are activated in D8. Therefore, if Insert is disabled in D7,
    // deactivate all styles in D8.
    if ($widget_settings['insert']) {
      foreach ($widget_settings['insert_styles'] as $style) {
        $style = preg_replace('/^image_/', '', $style);
        $styles[$style] = $style;
      }
    }

    return [
      'insert' => [
        'styles' => $styles,
        'default' => $widget_settings['insert_default'],
        'class' => $widget_settings['insert_class'],
        'width' => $widget_settings['insert_width'],
      ],
    ];
  }

}