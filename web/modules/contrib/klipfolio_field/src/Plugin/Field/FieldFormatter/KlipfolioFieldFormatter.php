<?php

namespace Drupal\klipfolio_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'klipfolio_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "klipfolio_field_formatter",
 *   label = @Translation("Klipfolio widget"),
 *   field_types = {
 *     "klipfolio"
 *   }
 * )
 */
class KlipfolioFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => 400,
      'theme' => 'Light',
      'sizing_type' => 'fixed',
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['sizing_type'] = [
      '#type' => 'radios',
      '#title' => t('Sizing options'),
      '#default_value' => $this->getSetting('sizing_type'),
      '#options' => array(
        'responsive' => $this->t('Responsive'),
        'custom' => $this->t('None (define in your custom CSS)'),
        'fixed' => $this->t('Fixed'),
      ),
      '#required' => TRUE,
    ];
    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width in pixels'),
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['width']['#states']['visible'][] = [
      ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][sizing_type]"]' => ['value' => 'fixed'],
    ];
    $elements['theme'] = [
      '#type' => 'select',
      '#options' => ['Light' => $this->t('Light'), 'Dark' => $this->t('Dark')],
      '#title' => t('Theme'),
      '#default_value' => $this->getSetting('theme'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $width = '';
    switch ($settings['sizing_type']) {
      case 'fixed':
        $width = t(
          'fixed width: @width<em>px</em>',
          [
            '@width' => $settings['width'],
          ]);
        break;

      case 'custom':
        $width = $this->t("width defined in your theme");
        break;

      case 'responsive':
        $width = $this->t("responsive");
        break;
    }
    $summary[] = $this->t(
      'Theme: @theme, @width',
      [
        '@width' => $width,
        '@theme' => $settings['theme']
      ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#attached' => [
          'library' => [
            'klipfolio_field/klipfolio_field',
          ],
        ],
        '#theme' => 'klipfolio_field',
        '#attributes' => [
          'class' => ['klipfolio-container'],
          'id' => 'kf-embed-container-' . $item->value,
          'data-klipfolio-id' => $item->value,
          'data-klipfolio-theme' => $settings['theme'],
          'data-klipfolio-title' => $item->title,
        ],
      ];
      if ($settings['sizing_type'] == 'fixed') {
        $elements[$delta]['#attributes']['data-klipfolio-width'] = $settings['width'];
      }
      if ($settings['sizing_type'] == 'responsive') {
        $elements[$delta]['#attributes']['class'][] = Html::cleanCssIdentifier('responsive-widget');
      }
    }

    return $elements;
  }

}
