<?php

namespace Drupal\responsive_class_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'responsive_class_widget'.
 *
 * This widget provides a details wrapper with select elements to choose
 * the configured values for every enabled breakpoint.
 *
 * @FieldWidget(
 *   id = "responsive_class_widget",
 *   module = "responsive_class_field",
 *   label = @Translation("Responsive class"),
 *   field_types = {
 *     "responsive_class"
 *   }
 * )
 */
class ResponsiveClassWidget extends WidgetBase {

  /**
   * The available options for every breakpoint.
   *
   * @var array
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // By default, show select elements inline.
      'inline' => TRUE,
      // The default width of online select elements.
      'inline_width' => 125,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use inline blocks for breakpoint settings'),
      '#description' => $this->t('If checked, the breakpoints and their select boxes will be shown beside each other rather than stacked.'),
      '#default_value' => !empty($this->getSetting('inline')),
    ];
    $element['inline_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Inline block width'),
      '#description' => $this->t('The width of each inline block in pixels.'),
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('inline_width'),
      '#min' => 1,
      '#states' => [
        'visible' => [
          '[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][inline]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = !empty($this->getSetting('inline')) ?
      $this->t('Use inline blocks: @width', [
        '@width' => $this->getSetting('inline_width') . 'px',
      ]) :
      $this->t('Stacked display');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the field value of this field item.
    $value = isset($items[$delta]->value) ? $items[$delta]->value : [];
    // Simplify the field value to an associative array keyed by breakpoint
    // ID with the selected option value as value. Empty option values won't
    // be available within this array.
    $values = [];
    foreach ($value as $item) {
      $values[$item['breakpoint_id']] = $item['value'];
    }

    // Add a details wrapper.
    $element += [
      '#type' => 'details',
      '#tree' => TRUE,
      // Show opened, if any selected option value is not the empty
      // value.
      '#open' => !empty($values),
    ];

    // Get configured breakpoints from field storage settings.
    $breakpoints = $this->fieldDefinition->getSetting('breakpoints');
    // Get empty option value from field storage settings.
    $empty_value = $this->fieldDefinition->getSetting('empty_value');
    // Get available options.
    $options = $this->getOptions();

    // Whether to use inline breakpoint settings.
    $inline = !empty($this->getSetting('inline'));
    $inline_width = $this->getSetting('inline_width');

    // Create a child element for each breakpoint.
    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      $element[$breakpoint_id] = [
        '#type' => 'container',
        'breakpoint_id' => [
          '#type' => 'hidden',
          '#value' => $breakpoint_id,
        ],
      ];

      // Select options for the breakpoint.
      $element[$breakpoint_id]['value'] = [
        '#type' => 'select',
        '#title' => $breakpoint['label'],
        '#options' => $options,
        '#default_value' => isset($values[$breakpoint_id]) ? $values[$breakpoint_id] : $empty_value,
        '#multiple' => FALSE,
      ];

      // Add inline styles, if inline behavior has been configured.
      if ($inline) {
        $element[$breakpoint_id]['#attributes'] = [
          'style' => "display: inline-block; width: {$inline_width}px; height: auto;",
        ];
      }
    }

    return ['value' => $element];
  }

  /**
   * Return the array of options for the widget.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions() {
    if (!isset($this->options)) {
      // Limit the settable options for the current user account.
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getSetting('allowed_values');

      $empty_value = $this->fieldDefinition->getSetting('empty_value');

      $options = [$empty_value => '---'] + $options;
      array_walk_recursive($options, [$this, 'sanitizeLabel']);

      $this->options = $options;
    }
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

}
