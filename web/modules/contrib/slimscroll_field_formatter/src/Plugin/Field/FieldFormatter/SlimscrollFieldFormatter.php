<?php

namespace Drupal\slimscroll_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'slimscroll_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "slimscroll_field_formatter",
 *   module = "slimscroll_field_formatter",
 *   label = @Translation("Slim Scroll"),
 *   field_types = {
 *     "string_long",
 *     "text_with_summary",
 *     "text_long",
 *   }
 * )
 */
class SlimscrollFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Configure Slim Scrollbar');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'height' => '200',
      'size' => '10',
      'position' => 'right',
      'color' => '#0000ff',
      'alwaysVisible' => 0,
      'railVisible' => 1,
      'railColor' => '#222',
      'railOpacity' => 0.3,
      'wheelStep' => 10,
      'allowPageScroll' => 1,
      'disableFadeOut' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
    ];
    $element['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size'),
      '#default_value' => $this->getSetting('size'),
    ];
    $element['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => [
        'right' => $this->t('Right'),
        'left' => $this->t('Left'),
      ],
      '#default_value' => $this->getSetting('position'),
    ];
    $element['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#default_value' => $this->getSetting('color'),
    ];
    $element['alwaysVisible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always Visible'),
      '#default_value' => $this->getSetting('alwaysVisible'),
    ];
    $element['railVisible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rail Visible'),
      '#default_value' => $this->getSetting('railVisible'),
    ];
    $element['railColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Rail Color'),
      '#default_value' => $this->getSetting('railColor'),
    ];
    $element['railOpacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Rail Opacity'),
      '#options' => [
        '0.1' => 0.1,
        '0.2' => 0.2,
        '0.3' => 0.3,
        '0.4' => 0.4,
        '0.5' => 0.5,
        '0.6' => 0.6,
        '0.7' => 0.7,
        '0.8' => 0.8,
        '0.9' => 0.9,
      ],
      '#default_value' => $this->getSetting('railOpacity'),
    ];
    $element['wheelStep'] = [
      '#type' => 'select',
      '#title' => $this->t('Wheel Step'),
      '#options' => [
        '10' => 10,
        '20' => 20,
        '30' => 30,
        '40' => 40,
        '50' => 50,
        '60' => 60,
        '70' => 70,
        '80' => 80,
        '90' => 90,
        '100' => 100,
      ],
      '#default_value' => $this->getSetting('wheelStep'),
    ];
    $element['allowPageScroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow PageScroll'),
      '#default_value' => $this->getSetting('allowPageScroll'),
    ];
    $element['disableFadeOut'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable FadeOut'),
      '#default_value' => $this->getSetting('disableFadeOut'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $map_vars = [
        'height' => $this->getSetting('height'),
        'size' => $this->getSetting('size'),
        'position' => $this->getSetting('position'),
        'color' => $this->getSetting('color'),
        'alwaysVisible' => $this->getSetting('alwaysVisible'),
        'railVisible' => $this->getSetting('railVisible'),
        'railColor' => $this->getSetting('railColor'),
        'railOpacity' => $this->getSetting('railOpacity'),
        'wheelStep' => $this->getSetting('wheelStep'),
        'allowPageScroll' => $this->getSetting('allowPageScroll'),
        'disableFadeOut' => $this->getSetting('disableFadeOut'),
      ];
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
        '#prefix' => '<div class="slimScroll-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    $elements['#attached']['drupalSettings']['slimscroll']['view'] = $map_vars;
    $elements['#attached']['library'][] = 'slimscroll_field_formatter/slimscroll_field_formatter.bar';
    return $elements;
  }

}
