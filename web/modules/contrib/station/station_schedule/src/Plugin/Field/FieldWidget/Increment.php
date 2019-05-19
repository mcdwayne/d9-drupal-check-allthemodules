<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Plugin\Field\FieldWidget\Increment.
 */

namespace Drupal\station_schedule\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @todo.
 *
 * @FieldWidget(
 *   id = "station_schedule_increment",
 *   label = @Translation("Increment (Schedule)"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class Increment extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#default_value' => $items[$delta]->value ?: 60,
      '#options' => [
        1 => $this->t('1 Minute'),
        5 => $this->t('5 Minutes'),
        15 => $this->t('15 Minutes'),
        30 => $this->t('30 Minutes'),
        60 => $this->t('1 Hour'),
        120 => $this->t('2 Hours'),
      ],
      '#description' => $this->t("This is the minimum increment that programs can be scheduled in. <strong>Caution:</strong> Increasing this value on an existing schedule will probably cause weirdness."),
    ];
    return $element;
  }

}
