<?php

namespace Drupal\media_entity_libsyn\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_libsyn\Plugin\media\Source\Libsyn;

/**
 * Plugin implementation of the 'libsyn_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "libsyn_embed",
 *   label = @Translation("Libsyn embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class LibsynEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '700px',
      'height' => '90px',
      'custom_color' => '87A93A',
      'theme' => 'custom',
      'direction' => 'forward',
      'options' => [
        'autonext' => '',
        'thumbnail' => 'thumbnail',
        'autoplay' => '',
        'preload' => '',
        'no_addthis' => '',
        'render_playlist' => '',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player (including unit of measure). Suggested value: 700px'),
      '#element_validate' => [[$this, 'validateDimension']],
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player (including unit of measure). Suggested value: 90px'),
      '#element_validate' => [[$this, 'validateDimension']],
    ];

    $elements['theme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme'),
      '#default_value' => $this->getSetting('theme'),
      '#required' => TRUE,
      '#description' => $this->t('Theme name'),
    ];

    $elements['direction'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Direction'),
      '#default_value' => $this->getSetting('direction'),
      '#required' => TRUE,
      '#description' => $this->t('Direction'),
    ];

    $elements['custom_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom color'),
      '#default_value' => $this->getSetting('custom_color'),
      '#required' => TRUE,
      '#description' => $this->t('Custom color (6-character hex code)'),
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
   * Custom dimension validator method.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validateDimension(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (is_numeric($value)) {
      $form_state->setError($element, $this->t('Dimension must include unit of measure. E.g., "90<em>px</em>" or "100<em>%</em>"'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
      $this->t('Theme: @theme', [
        '@theme' => $this->getSetting('theme'),
      ]),
      $this->t('Custom color: @color', [
        '@color' => $this->getSetting('custom_color'),
      ]),
    ];

    $summary[] = $this->t('Options: @options', [
      '@options' => implode(', ', array_intersect_key($this->getEmbedOptions(), array_flip($this->getSetting('options')))),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /* @var \Drupal\media\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if (($type = $media_entity->getSource()) && $type instanceof Libsyn) {
      /* @var \Drupal\media\MediaSourceInterface $item */
      foreach ($items as $delta => $item) {
        if ($episode_id = $type->getMetadata($media_entity, 'episode_id')) {
          $element[$delta] = [
            '#theme' => 'media_libsyn_embed',
            '#episode_id' => $episode_id,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
            '#embed_theme' => $this->getSetting('theme'),
            '#custom_color' => $this->getSetting('custom_color'),
            '#direction' => $this->getSetting('direction'),
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
   *   Embed options.
   */
  protected function getEmbedOptions() {
    return [
      'autonext' => $this->t('Auto Next'),
      'thumbnail' => $this->t('Show thumbnail'),
      'autoplay' => $this->t('Autoplay'),
      'preload' => $this->t('Preload'),
      'no_addthis' => $this->t('No Addthis'),
      'render_playlist' => $this->t('Render playlist'),
    ];
  }

}
