<?php

namespace Drupal\media_entity_soundcloud\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_soundcloud\Plugin\media\Source\Soundcloud;

/**
 * Plugin implementation of the 'soundcloud_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "soundcloud_embed",
 *   label = @Translation("Soundcloud embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class SoundcloudEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => 'visual',
      'width' => '100%',
      'height' => '450',
      'options' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => [
        'visual' => $this->t('Visual'),
        'classic' => $this->t('Classic'),
      ],
      '#default_value' => $this->getSetting('type'),
      '#description' => $this->t('The type of embed.'),
    ];

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player.'),
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height (px) of embedded player. Suggested values: 450 for the visual type and 166 for classic.'),
    ];

    $elements['options'] = [
      '#title' => $this->t('Options'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('options'),
      '#options' => $this->getEmbedOptions(),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Type: @type', [
        '@type' => $this->getSetting('type'),
      ]),
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
    ];
    $options = $this->getSetting('options');
    if (count($options)) {
      $summary[] = $this->t('Options: @options', [
        '@options' => implode(', ', array_intersect_key($this->getEmbedOptions(), array_flip($this->getSetting('options')))),
      ]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();

    $element = [];
    if (($source = $media->getSource()) && $source instanceof Soundcloud) {
      /** @var \Drupal\media\MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if ($source_id = $source->getMetadata($media, 'source_id')) {
          $element[$delta] = [
            '#theme' => 'media_soundcloud_embed',
            '#source_id' => $source_id,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
            '#type' => $this->getSetting('type'),
            '#options' => $this->getSetting('options'),
          ];
        }
      }
    }
    return $element;
  }

  /**
   * Returns an array of options for the embedded player.
   *
   * @return array
   *   An array of options for the embedded player.
   */
  protected function getEmbedOptions() {
    return [
      'auto_play' => $this->t('Autoplay'),
      'hide_related' => $this->t('Hide related'),
      'show_comments' => $this->t('Show comments'),
      'show_user' => $this->t('Show user'),
      'show_reposts' => $this->t('Show reposts'),
    ];
  }

}
