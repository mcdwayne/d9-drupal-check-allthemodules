<?php

namespace Drupal\commerce_product_review\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_product_review_star_rating' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_review_star_rating",
 *   label = @Translation("Stars rating"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class StarsRatingWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? (string) $items[$delta]->value : NULL;
    $default_value = $value ?: '1';
    $rating_options = [
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
    ];
    if (!$items->getFieldDefinition()->isRequired() || empty($value)) {
      $rating_options = ['0' => '0'] + $rating_options;
    }

    $field_name = $this->fieldDefinition->getName();
    $select_element_name_parts = $form['#parents'];
    $select_element_name_parts[] = $field_name;
    $select_element_name_parts[] = $delta;
    $select_element_name_parts[] = 'value';
    $select_element_name_selector = array_shift($select_element_name_parts);
    $select_element_name_selector .= '[' . implode('][', $select_element_name_parts) . ']';
    $select_element_name_selector = sprintf('select[name="%s"]', $select_element_name_selector);

    $element += [
      '#type' => 'fieldset',
      'value' => [
        '#type' => 'select',
        '#options' => $rating_options,
        '#default_value' => $default_value,
      ],
      'rateit' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => '',
        '#attributes' => [
          'class' => ['rateit'],
          'data-rateit-backingfld' => $select_element_name_selector,
          'data-rateit-resetable' => $items->getFieldDefinition()->isRequired() ? 'false' : 'true',
          // Note, that we have to set 0 as min because without reset button,
          // the min value never can be set.
          'data-rateit-min' => 0,
          'data-rateit-max' => 5,
          'data-rateit-step' => 1,
        ],
      ],
    ];
    $element['#attached']['library'] = ['commerce_product_review/rateitjs'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if ($value['value'] === '0') {
        // Explicitly set zero values to NULL.
        $value['value'] = NULL;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product_review' && $field_name == 'rating_value';
  }

}
