<?php

/**
 * @file
 * Contains \Drupal\readmore\Plugin\field\FieldFormatter\ReadmoreFormatter.
 */

namespace Drupal\readmore\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'readmore' formatter.
 *
 * @FieldFormatter(
 *   id = "readmore",
 *   label = @Translation("Readmore"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class ReadmoreFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length'   => '500',
      'trim_on_break' => TRUE,
      'show_readmore' => TRUE,
      'show_readless' => FALSE,
      'ellipsis'      => TRUE,
      'wordsafe'      => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['trim_length'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Trim link text length'),
      '#field_suffix'  => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min'           => 1,
      '#description'   => $this->t('Leave blank to allow unlimited link text lengths.'),
    ];

    $elements['trim_on_break'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Trim on @break', [
        '@break' => '<!--break-->',
      ]),
      '#description'   => $this->t('If @break not found in the text then trim length used.', [
        '@break' => '<!--break-->',
      ]),
      '#default_value' => $this->getSetting('trim_on_break'),
    ];

    $elements['show_readmore'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show read more'),
      '#default_value' => $this->getSetting('show_readmore'),
    ];

    $elements['show_readless'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show read less'),
      '#default_value' => $this->getSetting('show_readless'),
    ];

    $elements['ellipsis'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add ellipsis'),
      '#default_value' => $this->getSetting('ellipsis'),
    ];

    $elements['wordsafe'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Truncate on a word boundary'),
      '#default_value' => $this->getSetting('wordsafe'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    if (!empty($settings['trim_on_break'])) {
      $summary[] = $this->t('Trim on @break', [
        '@break' => '<!--break-->',
      ]);
    }
    elseif (!empty($settings['trim_length'])) {
      $summary[] = $this->t('Text trimmed to @limit characters', [
        '@limit' => $settings['trim_length'],
      ]);
    }

    if (!empty($settings['show_readmore'])) {
      $summary[] = $this->t('With read more link');
    }
    else {
      $summary[] = $this->t('Without read more link');
    }

    if (!empty($settings['show_readless'])) {
      $summary[] = $this->t('With read less link');
    }
    else {
      $summary[] = $this->t('Without read less link');
    }

    if (!empty($settings['ellipsis'])) {
      $summary[] = $this->t('With ellipsis');
    }
    else {
      $summary[] = $this->t('Without ellipsis');
    }

    if (!empty($settings['wordsafe'])) {
      $summary[] = $this->t('Truncate on a word boundary');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $settings = $this->getSettings();

    $route_name = \Drupal::routeMatch()->getRouteName();
    $current_url = Url::fromRoute($route_name);

    $read_less = $this->t('Read less');
    $read_more = $this->t('Read more');

    // Prepare readless link.
    $link_less = Link::fromTextAndUrl($read_less, $current_url);
    $link_less = $link_less->toRenderable();
    $link_less['#attributes']['class'][] = 'readless-link';

    // Prepare readmore link.
    $link_more = Link::fromTextAndUrl($read_more, $current_url);
    $link_more = $link_more->toRenderable();
    $link_more['#attributes']['class'][] = 'readmore-link';

    foreach ($items as $delta => $item) {
      $text = $item->value;
      $text_length = Unicode::strlen($text);
      $trim_length = $settings['trim_length'];

      // Don't do anything if text length less than defined.
      if ($text_length > $trim_length) {
        // Add Read less if need.
        if ($settings['show_readless']) {
          $text .= ' ' . render($link_less);
        }

        // Get trimmed string.
        $summary = readmore_truncate_string(
          $text,
          isset($item->format) ? $item->format : NULL,
          $trim_length,
          $settings['wordsafe'],
          $settings['trim_on_break']
        );

        // Add readmore link.
        $summary .= '<span>';
        $summary .= $settings['ellipsis'] ? $this->t('...') : NULL;

        if ($settings['show_readmore']) {
          $summary .= render($link_more);
        }

        $summary .= '</span>';

        // Close all HTML tags.
        $summary = Html::normalize($summary);

        $elements[$delta] = [
          '#theme'   => 'readmore',
          '#summary' => Markup::create($summary),
          '#text'    => Markup::create($text),
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $text,
        ];
      }
    }

    $elements['#attached']['library'][] = 'readmore/readmore';

    return $elements;
  }

}
