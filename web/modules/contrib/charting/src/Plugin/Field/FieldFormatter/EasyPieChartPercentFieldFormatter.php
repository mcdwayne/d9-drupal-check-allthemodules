<?php

namespace Drupal\charting\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Easy pie chart percent formatter.
 *
 * Plugin implementation of the 'easy_pie_chart_percent_field_formatter'
 * formatter.
 *
 * @FieldFormatter(
 *   id = "easy_pie_chart_percent_field_formatter",
 *   label = @Translation("Percent circle"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class EasyPieChartPercentFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 50,
      'animate' => 1000,
      'line_width' => 3,
      'barcolor' => '#ef1e25',
      'trackcolor' => '#f2f2f2',
      'scalecolor' => '#dfe0e0',
      'linecap' => 'round',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'size' => [
        '#type' => 'number',
        '#title' => $this->t('Canvas size'),
        '#description' => $this->t('Size of the pie chart in px. It will always be a square.'),
        '#default_value' => $this->getSetting('size'),
      ],
      'animate' => [
        '#type' => 'number',
        '#title' => $this->t('Animate'),
        '#description' => $this->t('Time in milliseconds for a eased animation of the bar growing, or 1 to deactivate.'),
        '#min' => 1,
        '#default_value' => $this->getSetting('animate'),
      ],
      'line_width' => [
        '#type' => 'number',
        '#title' => $this->t('Line width'),
        '#description' => $this->t('Width of the bar line in px.'),
        '#min' => 1,
        '#default_value' => $this->getSetting('line_width'),
      ],
      'barcolor' => [
        '#type' => 'color',
        '#title' => $this->t('Bar color'),
        '#description' => $this->t('The color of the curcular bar.'),
        '#default_value' => $this->getSetting('barcolor'),
      ],
      'trackcolor' => [
        '#type' => 'color',
        '#title' => $this->t('Track color'),
        '#description' => $this->t('The color of the track for the bar.'),
        '#default_value' => $this->getSetting('trackcolor'),
      ],
      'scalecolor' => [
        '#type' => 'color',
        '#title' => $this->t('Scale color'),
        '#description' => $this->t('The color of the scale lines.'),
        '#default_value' => $this->getSetting('scalecolor'),
      ],
      'linecap' => [
        '#type' => 'select',
        '#title' => $this->t('Line cap'),
        '#options' => [
          'butt' => $this->t('Butt'),
          'round' => $this->t('Round'),
          'square' => $this->t('Square'),
        ],
        '#description' => $this->t('Defines how the ending of the bar line looks like.'),
        '#default_value' => $this->getSetting('linecap'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $aux = $this->t('Size: @size px', ['@size' => $this->getSetting('size')]);
    $aux .= ', ' . $this->t('Animate: @animate ms', ['@animate' => $this->getSetting('animate')]);
    $aux .= ', ' . $this->t('Line width: @linewidth px', ['@linewidth' => $this->getSetting('line_width')]);
    $aux .= ', ' . $this->t('Line cap: @linecap', ['@linecap' => $this->getSetting('linecap')]);
    $summary[] = $aux;

    $aux = $this->t('Bar color: @barcolor', ['@barcolor' => $this->getSetting('barcolor')]);
    $aux .= ', ' . $this->t('Track color: @trackcolor', ['@trackcolor' => $this->getSetting('trackcolor')]);
    $aux .= ', Scale color: ' . $this->getSetting('scalecolor');
    $summary[] = $aux;

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
    // Calculate value a label.
    $safeValue = nl2br(Html::escape($item->value));
    $percent = $safeValue;
    // If a max value is set.
    if ($settings['max']) {
      // Calculate percent.
      $percent = ($safeValue * 100) / $settings['max'];
    }
    // Build the render array.
    $id = uniqid('EasyPieChartPercentFieldFormatter_');
    $element = [
      '#theme' => 'easy_pie_chart_percent_field_formatter',
      '#id' => $id,
      '#percent' => $percent,
      '#value' => $settings['prefix'] . $safeValue . $settings['suffix'],
      '#attached' => [
        'library' => [
          'charting/rendro-easy_pie_chart',
        ],
        'drupalSettings' => [
          'charting' => [
            $id => [
              'size' => $this->getSetting('size'),
              'animate' => $this->getSetting('animate'),
              'line_width' => $this->getSetting('line_width'),
              'barcolor' => $this->getSetting('barcolor'),
              'trackcolor' => $this->getSetting('trackcolor'),
              'scalecolor' => $this->getSetting('scalecolor'),
              'linecap' => $this->getSetting('linecap'),
            ],
          ],
        ],
      ],
    ];
    return $element;
  }

}
