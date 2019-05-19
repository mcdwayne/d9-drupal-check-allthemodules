<?php

namespace Drupal\soundcloudfield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'soundcloud_url' widget.
 *
 * @FieldWidget(
 *   id = "soundcloud_url",
 *   module = "soundcloudfield",
 *   label = @Translation("SoundCloud URL"),
 *   field_types = {
 *     "soundcloud"
 *   }
 * )
 */
class SoundCloudWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // todo: investigate
    // $settings = parent::defaultSettings();

    return [
      'url' => '',
      'placeholder_url' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    // Build the element render array.
    $element['url'] = array(
      '#type' => 'url', // investigate, other types? (textfield)
      '#title' => $this->t('SoundCloud URL'),
      '#placeholder' => $this->getSetting('placeholder_url'), // investigate
      '#default_value' => isset($item->url) ? $item->url : NULL,
      '#element_validate' => [[get_called_class(), 'validateSoundCloudUriElement']],
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    );

    if (empty($element['url']['#description'])) {
      $element['url']['#description'] = $this->t('Enter the SoundCloud URL. A valid example: https://soundcloud.com/archives-5/purl-form-is-emptiness.');
    }

    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['placeholder_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for URL'),
      '#default_value' => $this->getSetting('placeholder_url'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $placeholder_url = $this->getSetting('placeholder_url');
    if (empty($placeholder_url)) {
      $summary[] = $this->t('No placeholders');
    }
    else {
      if (!empty($placeholder_url)) {
        $summary[] = $this->t('URL placeholder: @placeholder_url', array('@placeholder_url' => $placeholder_url));
      }
    }

    return $summary;
  }

  /**
   * Form element validation handler for the 'url' element.
   */
  public static function validateSoundCloudUriElement($element, FormStateInterface $form_state, $form) {
    $input = $element['#value'];

    if (!empty($input) && !preg_match('@^https?://soundcloud\.com/([^"\&]+)@i', $input, $matches)) {
      $form_state->setError($element, t('Please provide a valid SoundCloud URL.'));
    }
  }

}
