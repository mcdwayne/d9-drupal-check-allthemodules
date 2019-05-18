<?php

namespace Drupal\bynder_select2\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'bynder_select2_simple_widget' widget.
 *
 * @FieldWidget(
 *   id = "bynder_select2_simple_widget",
 *   label = @Translation("Bynder select2"),
 *   field_types = {
 *     "list_string",
 *     "list_integer"
 *   },
 *   multiple_values = TRUE
 * )
 */
class BynderSelect2SimpleWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $element = parent::formElement(
      $items,
      $delta,
      $element,
      $form,
      $form_state
    );

    $field_name = $this->fieldDefinition->getFieldStorageDefinition()->getName(
    );

    $element += [
      '#type' => 'bynder_select2_simple_element',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOptions($items),
      // Do not display a 'multiple' select box if there is only one option.
      '#placeholder' => $this->getSetting('placeholder'),
      '#multiple' => $this->multiple && count($this->options) > 1,
    ];

    if (isset($element['#options']['_none'])) {
      $element['#options'] = ['' => ''] + $element['#options'];
      unset($element['#options']['_none']);
    }

    $class = 'bynder-select2-' . hash(
        'md5',
        Html::getUniqueId('bynder-select2-' . $field_name)
      );

    $select2_settings = [
      'selector' => '.' . $class,
      'field_name' => $field_name,
      'settings' => $this->getSettings(),
      'placeholder_text' => $element['#placeholder_text'],
    ];

    $element['#attached']['drupalSettings']['bynder_select2'][$class] = $select2_settings;
    $element['#attributes']['class'][] = $class;
    $element['#attached']['library'][] = 'bynder_select2/bynder_select2.widget';

    return $element;
  }

}
