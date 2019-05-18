<?php

namespace Drupal\charting\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'percent_bar_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "percent_bar_field_formatter",
 *   label = @Translation("Percent bar"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class PercentBarFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'background-color' => '#eeeeee',
      'bar-color' => '#3dbdd3',
      'text-color' => '#fff',
      'speed' => '2',
      'transition' => 'cubic-bezier(0.77, 0, 0.175, 1)',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'background-color' => [
        '#type' => 'color',
        '#title' => $this->t('Background color'),
        '#description' => $this->t('The backgound color of the bar.'),
        '#default_value' => $this->getSetting('background-color'),
      ],
      'bar-color' => [
        '#type' => 'color',
        '#title' => $this->t('Bar color'),
        '#description' => $this->t('The bar color.'),
        '#default_value' => $this->getSetting('bar-color'),
      ],
      'text-color' => [
        '#type' => 'color',
        '#title' => $this->t('Bar text color'),
        '#description' => $this->t('The text color into the bar.'),
        '#default_value' => $this->getSetting('text-color'),
      ],
      'transition' => [
        '#type' => 'textfield',
        '#title' => $this->t('CSS animation'),
        '#description' => $this->t('The CSS transition.'),
        '#default_value' => $this->getSetting('transition'),
      ],
      'speed' => [
        '#type' => 'number',
        '#title' => $this->t('Animation speed'),
        '#description' => $this->t('Time in seconds for a eased animation of the bar growing, or 1 to deactivate.'),
        '#min' => 1,
        '#default_value' => $this->getSetting('speed'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $aux = $this->t('Background color: @data', ['@data' => $this->getSetting('background-color')]);
    $aux .= ', ' . $this->t('Bar color: @data', ['@data' => $this->getSetting('bar-color')]);
    $aux .= ', ' . $this->t('Text color: @data', ['@data' => $this->getSetting('text-color')]);
    $aux .= ', ' . $this->t('Speed: @data', ['@data' => $this->getSetting('speed')]);
    $summary[] = $aux;

    $summary[] = $this->t('Transition: @data', ['@data' => $this->getSetting('transition')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // Get field settings.
    $definition = $item->getFieldDefinition();
    $settings = $definition->getSettings();
    // Calculate a label value.
    $safeValue = nl2br(Html::escape($item->value));
    $percent = $safeValue;
    // If a max value is set.
    if ($settings['max']) {
      // Calculate percent.
      $percent = ($safeValue * 100) / $settings['max'];
    }
    // Build the render array.
    $id = uniqid('PercentBarFieldFormatter_');
    $element = [
      '#theme' => 'percent_bar_field_formatter',
      '#id' => $id,
      '#percent' => $percent,
      '#value' => $settings['prefix'] . $safeValue . $settings['suffix'],
      '#attached' => [
        'library' => [
          'charting/percent_bar_chart',
        ],
        'drupalSettings' => [
          'charting' => [
            $id => [
              'backgroundcolor' => $this->getSetting('background-color'),
              'barcolor' => $this->getSetting('bar-color'),
              'textcolor' => $this->getSetting('text-color'),
              'speed' => $this->getSetting('speed'),
              'transition' => $this->getSetting('transition'),
            ],
          ],
        ],
      ],
    ];
    return $element;
  }

}
