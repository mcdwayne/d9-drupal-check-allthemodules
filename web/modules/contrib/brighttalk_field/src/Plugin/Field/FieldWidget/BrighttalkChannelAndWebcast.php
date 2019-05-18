<?php

/**
 * @file
 * Contains \Drupal\brighttalk_field\Plugin\Field\FieldWidget\BrighttalkChannelAndWebcast.
 */

namespace Drupal\brighttalk_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *  id = "brighttalk_channel_and_webcast",
 *  label = @Translation("Channel ID & Webcast ID"),
 *  field_types = {"brighttalk_webcast"}
 * )
 */
class BrighttalkChannelAndWebcast extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['channel_id'] = [
      '#title' => $element['#title'],
      '#type' => 'textfield',
      '#default_value' => (isset($items[$delta]->channel_id)) ? $items[$delta]->channel_id : NULL,
      '#placeholder' => t('Channel ID'),
      '#description' => t('BrightTALK Channel ID.'),
    ];

    $element['webcast_id'] = [
      '#title' => $element['#title'],
      '#type' => 'textfield',
      '#default_value' => (isset($items[$delta]->webcast_id)) ? $items[$delta]->webcast_id : NULL,
      '#placeholder' => t('Webcast ID'),
      '#description' => t('BrightTALK Webcast ID.'),
    ];

    $element['embed'] = [
      '#type' => 'value',
      '#default_value' => '',
    ];

    return $element;
  }

}
