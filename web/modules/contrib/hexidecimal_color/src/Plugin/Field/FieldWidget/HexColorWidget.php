<?php

namespace Drupal\hexidecimal_color\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hexidecimal_color\Plugin\DataType\HexColorInterface;

/**
 * The default widget for (Hexidecimal) Color fields.
 *
 * @FieldWidget(
 *   id = "hexidecimal_color_widget",
 *   label = @Translation("Textfield Input"),
 *   field_types = {
 *      "hexidecimal_color"
 *   }
 * )
 */
class HexColorWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary['overview'] = $this->t('A textfield input that accepts a hexidecimal color string');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['color'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
      '#description' => $element['#description'],
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
    ];

    return $element;
  }

}
