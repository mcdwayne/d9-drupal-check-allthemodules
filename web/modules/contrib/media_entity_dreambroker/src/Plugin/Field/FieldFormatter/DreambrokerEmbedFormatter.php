<?php

namespace Drupal\media_entity_dreambroker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_dreambroker\Plugin\media\Source\Dreambroker;

/**
 * Plugin implementation of the 'dreambroker_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "dreambroker_embed",
 *   label = @Translation("Dream Broker embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class DreambrokerEmbedFormatter extends FormatterBase {

  /**
   * Extracts the embed code from a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string|null
   *   The embed code, or NULL if the field type is not supported.
   */
  protected function getEmbedCode(FieldItemInterface $item) {
    switch ($item->getFieldDefinition()->getType()) {
      case 'link':
        return $item->uri;

      case 'string':
      case 'string_long':
        return $item->value;

      default:
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $matches = [];

      foreach (Dreambroker::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $this->getEmbedCode($item), $item_matches)) {
          $matches[] = $item_matches;
        }
      }

      if (!empty($matches)) {
        $matches = reset($matches);
      }

      if (!empty($matches['channelid']) && !empty($matches['videoid'])) {
        $element[$delta] = [
          '#theme' => 'media_entity_dreambroker_iframe',
          '#url' => 'https://dreambroker.com/channel/' . $matches['channelid'] . '/iframe/' . $matches['videoid'],
          '#query' => [
            'autoplay' => $this->getSetting('autoplay'),
          ],
          '#attributes' => [
            'class' => ['dreambroker-iframe'],
          ],
        ];

        if ($this->getSetting('responsive')) {
          $element['#attached']['library'][] = 'media_entity_dreambroker/responsive_iframe';
          $element['#attributes']['class'][] = 'dreambroker-iframe-responsive';
        }
        else {
          $element[$delta]['#attributes']['width'] = $this->getSetting('width');
          $element[$delta]['#attributes']['height'][] = $this->getSetting('height');
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive' => TRUE,
      'width' => '854',
      'height' => '480',
      'autoplay' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['autoplay'] = [
      '#title' => $this->t('Autoplay'),
      '#type' => 'checkbox',
      '#description' => $this->t('Autoplay the video.'),
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $elements['responsive'] = [
      '#title' => $this->t('Responsive Video'),
      '#type' => 'checkbox',
      '#description' => $this->t("Make the video fill the width of it's container, adjusting to the size of the user's screen."),
      '#default_value' => $this->getSetting('responsive'),
    ];
    // Loosely match the name attribute so forms which don't have a field
    // formatter structure (such as the WYSIWYG settings form) are also matched.
    $responsive_checked_state = [
      'visible' => [
        [
          ':input[name*="responsive"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    $elements['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $dimensions = $this->getSetting('responsive') ? $this->t('Responsive') : $this->t('@widthx@height', ['@width' => $this->getSetting('width'), '@height' => $this->getSetting('height')]);
    $summary[] = $this->t('Embedded Dream Broker (@dimensions@autoplay).', [
      '@dimensions' => $dimensions,
      '@autoplay' => $this->getSetting('autoplay') ? $this->t(', autoplaying') : '',
    ]);
    return $summary;
  }

}
