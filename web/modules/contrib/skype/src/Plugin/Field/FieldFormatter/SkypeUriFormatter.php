<?php

/**
 * @file
 * Contains \Drupal\skype\Plugin\field\formatter\SkypeUriFormatter.
 */

namespace Drupal\skype\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'skype_button' formatter.
 *
 * @FieldFormatter(
 *   id = "skype_uri",
 *   label = @Translation("Skype URI"),
 *   field_types = {
 *     "skype"
 *   }
 * )
 */
class SkypeUriFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'link_text' => t('Skype me'),
        'action' => 'call',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['action'] = [
      '#type' => 'radios',
      '#title' => t('Choose what you\'d like your URI to do:'),
      '#options' => [
        'call' => t('Call'),
        'video' => t('Video chat'),
        'chat' => t('Chat'),
      ],
      '#default_value' => $this->getSetting('action'),
      '#required' => TRUE,
    ];

    $elements['link_text'] = [
      '#type' => 'textfield',
      '#title' => t('Choose the link text:'),
      '#default_value' => $this->getSetting('link_text'),
      '#required' => TRUE,
      '#description' => t('For example: "Call me" or "Video chat with me". You can use [skype:id] as token to use in your link text.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('URI action: @actions',
      ['@actions' => $settings['action']]);
    $summary[] = t('URI text: @text', ['@text' => $settings['link_text']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // Render each element as skype URI.
      $element[$delta] = [
        '#theme' => 'skype_uri',
        '#skype_id' => $item->value,
        '#settings' => $settings,
        '#langcode' => $item->getLangcode(),
      ];
    }

    return $element;
  }

}
