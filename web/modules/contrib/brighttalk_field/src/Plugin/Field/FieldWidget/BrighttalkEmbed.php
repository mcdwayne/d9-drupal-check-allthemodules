<?php

namespace Drupal\brighttalk_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Embed Code' widget.
 *
 * @FieldWidget(
 *  id = "brighttalk_embed",
 *  label = @Translation("Embed Code"),
 *  field_types = {"brighttalk_webcast"}
 * )
 */
class BrighttalkEmbed extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['embed'] = [
      '#title' => $element['#title'],
      '#type' => 'textarea',
      '#default_value' => (isset($items[$delta]->embed)) ? $items[$delta]->embed : NULL,
      '#empty_value' => '',
      '#placeholder' => t('Embed Code'),
      '#description' => t('BrightTALK embed code.'),
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    $element['channel_id'] = [
      '#type' => 'value',
      '#default_value' => (isset($items[$delta]->channel_id)) ? $items[$delta]->channel_id : NULL,
    ];

    $element['webcast_id'] = [
      '#type' => 'value',
      '#default_value' => (isset($items[$delta]->webcast_id)) ? $items[$delta]->webcast_id : NULL,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as &$item) {

      if (isset($item['embed']) && !empty($item['embed'])) {
        $item['channel_id'] = brighttalk_field_webcast_code_value($item['embed'], 'channelid');
        $item['webcast_id'] = brighttalk_field_webcast_code_value($item['embed'], 'communicationid');
      }
    }

    return $values;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validate($element, FormStateInterface $form_state) {
    // Channel ID not found in the embed code..
    if (!empty($element['#value'])) {

      $channel_id = brighttalk_field_webcast_code_value($element['#value'], 'channelid');
      $webcast_id = brighttalk_field_webcast_code_value($element['#value'], 'communicationid');

      if (empty($channel_id) || !is_numeric($channel_id)) {
        $form_state->setError($element, t("Embed must contain a Channel ID."));
      }

      // Webcast ID not found in the embed code..
      if (empty($webcast_id) || !is_numeric($webcast_id)) {
        $form_state->setError($element, t("Embed must contain a Webcast ID."));
      }
    }
  }

}
