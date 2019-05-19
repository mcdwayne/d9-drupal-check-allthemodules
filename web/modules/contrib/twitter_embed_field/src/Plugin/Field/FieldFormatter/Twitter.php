<?php

namespace Drupal\twitter_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field formatter for twitter fields.
 *
 * @FieldFormatter(
 *   id = "twitter_embed_field",
 *   label = @Translation("Twitter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class Twitter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['width'] = [
      '#title' => $this->t('Width (px)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
      '#description' => $this->t('The width of the widget in px.'),
    ];

    $elements['height'] = [
      '#title' => $this->t('Height (px)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#description' => $this->t('The height of the widget in px.'),
    ];

    $elements['theme'] = [
      '#title' => $this->t('Theme'),
      '#type' => 'select',
      '#options' => [
        'light' => 'Light',
        'dark' => 'Dark',
      ],
      '#default_value' => $this->getSetting('theme'),
      '#description' => $this->t('The theme to display the widget in.'),
    ];

    $elements['link_color'] = [
      '#title' => $this->t('Link color'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_color'),
      '#description' => $this->t('The link color used in the widget.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'height' => 300,
        'width' => 300,
        'theme' => 'light',
        'link_color' => '#2B7BB9',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Dimensions: @widthx@height', [
      '@width' => $this->getSetting('width'),
      '@height' => $this->getSetting('height'),
    ]);
    $summary[] = $this->t('Theme: @theme', ['@theme' => $this->getSetting('theme')]);
    $summary[] = $this->t('Link color: @link_color', ['@link_color' => $this->getSetting('link_color')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {

      $handle = strip_tags($item->value);

      $element = [
        '#type' => 'markup',
        '#attached' => ['library' => ['twitter_embed_field/twitter_api']],
        '#markup' => '
      <a class="twitter-timeline"
        href="https://twitter.com/' . $handle . '"
        data-width="' . $this->getSetting('width') . '"
        data-height="' . $this->getSetting('height') . '"
        data-theme="' . $this->getSetting('theme') . '"
        data-link-color="' . $this->getSetting('link_color') . '"
        >
      Tweets by ' . $handle . '
      </a>
      ',
      ];
      $elements[$delta] = $element;
    }
    return $elements;
  }
}