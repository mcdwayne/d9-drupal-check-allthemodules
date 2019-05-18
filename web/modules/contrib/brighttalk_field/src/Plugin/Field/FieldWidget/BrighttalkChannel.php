<?php

namespace Drupal\brighttalk_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Channel ID' widget.
 *
 * @FieldWidget(
 *  id = "brighttalk_channel",
 *  label = @Translation("Channel ID"),
 *  field_types = {"brighttalk_channel"}
 * )
 */
class BrighttalkChannel extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['channel_id'] = [
      '#title' => $element['#title'],
      '#type' => 'number',
      '#default_value' => (isset($items[$delta]->channel_id)) ? $items[$delta]->channel_id : NULL,
      '#placeholder' => t('Channel ID'),
      '#description' => t('BrightTALK Channel ID.'),
    ];

    $element['embed'] = [
      '#type' => 'value',
      '#default_value' => '',
    ];

    $element['webcast_id'] = [
      '#type' => 'value',
      '#default_value' => 0,
    ];

    return $element;
  }

}
