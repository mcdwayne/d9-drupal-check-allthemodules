<?php

namespace Drupal\stacks\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stacks\Widget\WidgetData;
//use Drupal\stacks\Widget\WidgetTemplates;
use Drupal\stacks\Entity\WidgetInstanceEntity;

/**
 * Plugin implementation of the 'widget_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "widget_formatter_type",
 *   label = @Translation("Display Stacks"),
 *   field_types = {
 *     "stacks_type"
 *   }
 * )
 */
class WidgetFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // Load the entities and base values.
    $entity = $item->getEntity();

    // Storing field properties to handle front-end editing.
    $field_properties = [
      'field_name' => $item->getFieldDefinition()->getName(),
      'delta' => $item->getName(),
    ];

    $row_value = $item->getValue();
    $widget_instance_entity = WidgetInstanceEntity::load($row_value['widget_instance_id']);

    if (!$widget_instance_entity) {
      return '';
    }

    // Load the stacks entity.
    $widget_entity = $widget_instance_entity->getWidgetEntity();

    // Now get the render array for outputting this widget.
    $widget_data = new WidgetData();
    return $widget_data->output($entity, $widget_instance_entity, $widget_entity, 'content', $field_properties);
  }

}
