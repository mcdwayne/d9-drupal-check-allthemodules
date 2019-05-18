<?php

namespace Drupal\audio_embed_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * A widget to input audio URLs.
 *
 * @FieldWidget(
 *   id = "audio_embed_field_textfield",
 *   label = @Translation("Audio Textfield"),
 *   field_types = {
 *     "audio_embed_field"
 *   },
 * )
 */
class AudioTextfield extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 60,
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
      '#allowed_providers' => $this->getFieldSetting('allowed_providers'),
      '#element_validate' => [
          [get_class($this), 'validateFormElement'],
      ],
    ];
    return $element;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (empty($value)) {
      return;
    }
    $provider_manager = \Drupal::service('audio_embed_field.provider_manager');
    $enabled_providers = $provider_manager->loadDefinitionsFromOptionList($element['#allowed_providers']);
    if (!$provider_manager->filterApplicableDefinitions($enabled_providers, $value)) {
      $form_state->setError($element, static::getProviderErrorMessage());
    }
  }

  /**
   * Get the error message indicating a provider could not be found.
   *
   * @return string
   *   The provider error message.
   */
  public static function getProviderErrorMessage() {
    $settings_link = Link::createFromRoute(t('Audio Embed Field settings page'), 'audio_embed_field.admin_settings_form');
    return t('Could not find an audio provider to handle the given URL. If you are trying to use something with an API, make sure credentials are set up at the @settings_page.',
      ['@settings_page' => $settings_link->toString()]);
  }

}
