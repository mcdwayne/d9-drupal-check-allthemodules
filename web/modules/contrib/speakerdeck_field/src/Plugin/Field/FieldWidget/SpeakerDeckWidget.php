<?php

namespace Drupal\speakerdeck_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;

/**
 * A widget bar.
 *
 * @FieldWidget(
 *   id = "speakerdeck_widget",
 *   label = @Translation("SpeakerDeck widget"),
 *   field_types = {
 *     "speakerdeck_field"
 *   }
 * )
 */
class SpeakerDeckWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'data_id' => '',
      'data_ratio' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var LinkItemInterface $item */
    $item = $items[$delta];

    $element['#type'] = 'fieldset';

    $element['data_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data ID'),
      '#description' => $this->t('The data ID from the SpeakerDeck embed code.'),
//      '#required' => TRUE,
      '#default_value' => isset($item->data_id) ? $item->data_id : NULL,
    ];

    $element['data_ratio'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data ratio'),
      '#description' => $this->t('The data ratio value from the SpeakerDeck embed code.'),
//      '#required' => TRUE,
      '#default_value' => isset($item->data_ratio) ? $item->data_ratio : NULL,
    ];

    return $element;
  }

}
