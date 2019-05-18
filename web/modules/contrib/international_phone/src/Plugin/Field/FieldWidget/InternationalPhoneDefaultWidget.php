<?php

namespace Drupal\international_phone\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Plugin implementation of the 'InternationalPhoneDefaultWidget' widget.
 *
 * @FieldWidget(
 *   id = "InternationalPhoneDefaultWidget",
 *   label = @Translation("Text field"),
 *   field_types = {
 *     "international_phone"
 *   }
 * )
 */
class InternationalPhoneDefaultWidget extends WidgetBase {

  /**
   * Define the form for the field type.
   *
   * Inside this method we can define the form used to edit the field type.
   *
   * Here there is a list of allowed element types: https://goo.gl/XVd4tA
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $formState) {
    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street'),
      '#description' => SafeMarkup::checkPlain($element['#description']),
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
      '#required' => $element['#required'],
      '#size' => 17,
      '#attributes' => ['class' => ['international_phone-number']],
      '#attached' => [
        'library' => ['international_phone/international_phone'],
      ],
    ];

    return $element;
  }

}
