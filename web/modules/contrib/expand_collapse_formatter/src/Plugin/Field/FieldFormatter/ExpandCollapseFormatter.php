<?php
/**
 * @file
 * Contains the ExpandCollapseFormatter class.
 */

namespace Drupal\expand_collapse_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The expand collapse formatter.
 *
 * @FieldFormatter(
 *   id = "expand_collapse_formatter",
 *   module = "expand_collapse_formatter",
 *   label = @Translation("Expand collapse formatter"),
 *   field_types = {
 *     "text_long",
 *     "string_long",
 *     "text_with_summary"
 *   }
 * )
 */
class ExpandCollapseFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'trim_length' => 300,
        'default_state' => 'collapsed',
        'link_text_open' => 'Show more',
        'link_text_close' => 'Show less',
        'link_class_open' => 'ecf-open',
        'link_class_close' => 'ecf-close',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['trim_length'] = [
      '#title' => t('Trim length'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    $element['default_state'] = [
      '#title' => t('Default state'),
      '#type' => 'select',
      '#options' => [
        'collapsed' => t('Collapsed'),
        'expanded' => t('Expanded'),
      ],
      '#default_value' => $this->getSetting('default_state'),
      '#required' => TRUE,
    ];

    $element['link_text_open'] = [
      '#title' => t('Link text (open)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_text_open'),
      '#required' => FALSE,
    ];

    $element['link_text_close'] = [
      '#title' => t('Link text (close)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_text_close'),
      '#required' => FALSE,
    ];

    $element['link_class_open'] = [
      '#title' => t('Link class (open)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_class_open'),
      '#required' => FALSE,
    ];

    $element['link_class_close'] = [
      '#title' => t('Link class (close)'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_class_close'),
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Trim length: @trim_length', ['@trim_length' => $this->getSetting('trim_length')]);
    $summary[] = t('Default state: @default_state', ['@default_state' => $this->getSetting('default_state')]);
    $summary[] = t('Link text (open): @link_text_open', ['@link_text_open' => $this->getSetting('link_text_open')]);
    $summary[] = t('Link text (close): @link_text_close', ['@link_text_close' => $this->getSetting('link_text_close')]);
    $summary[] = t('Link class (open): @link_class_open', ['@link_class_open' => $this->getSetting('link_class_open')]);
    $summary[] = t('Link class (close): @link_class_close', ['@link_class_close' => $this->getSetting('link_class_close')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      if ($item->processed != NULL) {
        $output = $item->processed;
      }
      else {
        $output = $item->value;
      }
      // Render each element as markup.
      $element[$delta] = [
        '#theme' => 'expand_collapse_formatter',
        '#value' => $output,
        '#trim_length' => $this->getSetting('trim_length'),
        '#default_state' => $this->getSetting('default_state'),
        '#link_text_open' => $this->getSetting('link_text_open'),
        '#link_text_close' => $this->getSetting('link_text_close'),
        '#link_class_open' => $this->getSetting('link_class_open'),
        '#link_class_close' => $this->getSetting('link_class_close'),
        '#attached' => [
          'library' => [
            'expand_collapse_formatter/expand_collapse_formatter',
          ],
        ],
      ];
    }

    return $element;
  }

}
