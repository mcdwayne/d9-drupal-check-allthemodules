<?php

namespace Drupal\read_more_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'read_more_field' widget.
 *
 * @FieldWidget(
 *   id = "read_more_widget",
 *   module = "read_more_field",
 *   label = @Translation("Read more widget"),
 *   field_types = {
 *     "read_more"
 *   }
 * )
 */
class ReadMoreDefaultWidget extends WidgetBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_value = $items[$delta]->getValue();

    $teaser_value = isset($field_value['teaser_value']) ? $field_value['teaser_value'] : '';
    $teaser_format = isset($field_value['teaser_format']) ? $field_value['teaser_format'] : NULL;
    $hidden_value = isset($field_value['hidden_value']) ? $field_value['hidden_value'] : '';
    $hidden_format = isset($field_value['hidden_format']) ? $field_value['hidden_format'] : NULL;

    return [
      '#type' => 'details',
      '#title' => $element['#title'],
      '#open' => TRUE,
      'teaser' => [
        '#title' => $this->t('Teaser'),
        '#type' => 'text_format',
        '#format' => $teaser_format,
        '#default_value' => $teaser_value,
      ],
      'hidden' => [
        '#title' => $this->t('Hidden'),
        '#type' => 'text_format',
        '#format' => $hidden_format,
        '#default_value' => $hidden_value,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      $values[$key]['teaser_value'] = $value['teaser']['value'];
      $values[$key]['teaser_format'] = $value['teaser']['format'];
      unset($values[$key]['teaser']);

      $values[$key]['hidden_value'] = $value['hidden']['value'];
      $values[$key]['hidden_format'] = $value['hidden']['format'];
      unset($values[$key]['hidden']);
    }
    return $values;
  }

}
