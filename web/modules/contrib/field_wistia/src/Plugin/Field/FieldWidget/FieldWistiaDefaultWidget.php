<?php

/**
 * @file
 * Contains \Drupal\field_wistia\Plugin\Field\FieldWidget\FieldWistiaDefaultWidget.
 */

namespace Drupal\field_wistia\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_wistia' widget.
 *
 * @FieldWidget(
 *   id = "field_wistia",
 *   label = @Translation("Field wistia"),
 *   field_types = {
 *     "field_wistia"
 *   },
 *   settings = {
 *     "placeholder_url" = ""
 *   }
 * )
 */
class FieldWistiaDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['input'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->input) ? $items[$delta]->input : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => 255,
      '#element_validate' => array(array($this, 'validateInput')),
    ];

    if ($element['input']['#description'] == '') {
      $element['input']['#description'] = t('Enter the Wistia URL. Valid URL formats include: http://www.wistia.com/medias/g5pnf59ala');
    }

    // Shows up below the field on node edit page if no custom message set.
    if (isset($items->get($delta)->video_id)) {
      $element['video_id'] = [
        '#prefix' => '<div class="wistia-video-id">' ,
        '#markup' =>  t('Wistia video ID: @video_id', array('@video_id' => $items->get($delta)->video_id)),
        '#suffix' => '</div>'
      ];

    }

    return $element;
  }

  /**
   * Validation for the Wistia field itself.
   */
  function validateInput($element, FormStateInterface &$form_state, $form) {
    $input = $element['#value'];

    $video_id = field_wistia_get_video_id($input);

    if ($video_id) {
      $video_id_element = array(
        '#parents' => $element['#parents'],
      );
      array_pop($video_id_element['#parents']);
      $video_id_element['#parents'][] = 'video_id';
      $form_state->setValueForElement($video_id_element, $video_id);
    }
    elseif (!empty($input)) {
      $form_state->setError($element, t('Please provide a valid Wistia URL.'));
    }
  }

}
