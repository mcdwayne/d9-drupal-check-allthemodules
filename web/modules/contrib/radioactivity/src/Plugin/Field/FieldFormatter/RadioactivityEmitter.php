<?php

namespace Drupal\radioactivity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\radioactivity\Incident;

/**
 * Plugin implementation of the 'radioactivity_emitter' formatter.
 *
 * @FieldFormatter(
 *   id = "radioactivity_emitter",
 *   label = @Translation("Emitter"),
 *   field_types = {
 *     "radioactivity"
 *   }
 * )
 */
class RadioactivityEmitter extends FormatterBase {

  /**
   * The emission counter.
   *
   * Used to uniquely identify an emitter on a page. This was build as a static
   * counter to work across multiple entities and/or multiple fields displayed
   * on the same page.
   *
   * @var int
   */
  protected static $emitCount = 0;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'energy' => 10,
      'display' => 'none',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
      'energy' => [
        '#title' => $this->t('Energy'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#description' => $this->t('The amount of energy to emit when this field is displayed. Examples: 0.5, 10.'),
        '#pattern' => '[0-9]+(\.[0-9]+)?',
        '#default_value' => $this->getSetting('energy'),
      ],
      'display' => [
        '#title' => $this->t('Display'),
        '#type' => 'select',
        '#options' => [
          'none' => $this->t('Only emit'),
          'raw' => $this->t('Energy level + emit'),
        ],
        '#default_value' => $this->getSetting('display'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Emit: @energy', ['@energy' => $this->getSetting('energy')]);
    switch ($this->getSetting('display')) {
      case 'none':
        $summary[] = $this->t('Only emit');
        break;

      case 'raw':
        $summary[] = $this->t('Display energy level');
        break;
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      $incident = Incident::createFromFieldItemsAndFormatter($items, $item, $this);

      $key = 'ra_emit_' . self::$emitCount++;

      $elements[$delta] = [
        '#attached' => [
          'library' => ['radioactivity/triggers'],
          'drupalSettings' => [
            $key => $incident->toJson(),
          ],
        ],
      ];

      switch ($this->getSetting('display')) {
        case 'raw':
          $elements[$delta]['#markup'] = $this->viewValue($item);
          break;
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {

    $build = parent::view($items, $langcode);
    // If 'none' is chosen (No value - only emit), we do not want this formatter
    // to be rendered as field (it would be rendered in an empty wrapper diff).
    // We only use the children which contain the energy emitter in "#attached".
    if ($this->getSetting('display') == 'none') {
      $children = Element::children($build);
      $build = array_intersect_key($build, $children);
    }

    return $build;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    return $item->energy;
  }

}
