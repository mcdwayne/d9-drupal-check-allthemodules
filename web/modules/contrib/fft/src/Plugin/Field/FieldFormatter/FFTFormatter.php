<?php

namespace Drupal\fft\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'fft_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "fft_formatter",
 *   label = @Translation("Formatter Template"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 * )
 */
class FFTFormatter extends EntityReferenceFormatterBase {
  const FFT_PREPROCESS = 'fft.preprocess';

  /**
   * Check is reference type.
   *
   * @param string $type
   *   The type of field.
   *
   * @return bool
   *   Return value.
   */
  private static function isReference($type) {
    return substr($type, 0, 16) === 'entity_reference';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'template' => '',
      'image_style_1' => '',
      'image_style_2' => '',
      'settings' => '',
      'reset' => 1,
      'isNew' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();
    $field['type'] = $this->fieldDefinition->getType();
    $fft_templates = fft_get_templates();
    $options_set = $fft_templates['templates'];
    $form['#attached']['library'][] = 'fft/backend';
    if ($settings['isNew'] == 0) {
      $fft_templates['settings'][$settings['template']] = $settings['settings'];
    }
    $form['#attached']['drupalSettings']['fft'] = !empty($fft_templates['settings']) ? $fft_templates['settings'] : [];

    $form['isNew'] = [
      '#type' => 'hidden',
      '#value' => 0,
    ];

    $form['template'] = [
      '#title' => $this->t('Template'),
      '#type' => 'select',
      '#options' => $options_set,
      '#default_value' => $settings['template'],
      '#attributes' => ['class' => ['fft-template']],
    ];

    $form['reset'] = [
      '#type' => 'select',
      '#title' => $this->t('Reset'),
      '#options' => [
        0 => 'No',
        1 => 'Yes',
      ],
      '#default_value' => $settings['reset'],
      '#description' => $this->t('Reset default Drupal field wrapper markup.'),
    ];

    switch ($field['type']) {
      case 'image':
        $image_style_options = image_style_options();
        $form['image_style_1'] = [
          '#type' => 'select',
          '#title' => $this->t('Image Styles 1'),
          '#options' => $image_style_options,
          '#default_value' => $settings['image_style_1'],
        ];

        $form['image_style_2'] = [
          '#type' => 'select',
          '#title' => $this->t('Image Styles 2'),
          '#options' => $image_style_options,
          '#default_value' => $settings['image_style_2'],
        ];
        break;
    }
    $settings_des[] = $this->t('Add settings extras for template, one setting per line with syntax key = value.');
    $settings_des[] = $this->t('Support array like key[] = value or key[name] = value.');

    $form['settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Settings Extras'),
      '#default_value' => '',
      '#attributes' => ['class' => ['fft-settings']],
    ];

    $form['settings_des'] = [
      '#type' => 'details',
      '#title' => $this->t('More Information'),
      '#open' => FALSE,
    ];

    $form['settings_des']['info'] = [
      '#type' => 'markup',
      '#markup' => nl2br(implode("\r\n", $settings_des)),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Formatter Template');
    if ($this->getSetting('template') != '') {
      $fft_template = fft_get_templates('fft');
      if (!empty($fft_template['templates'])) {
        foreach ($fft_template['templates'] as $name => $title) {
          if ($this->getSetting('template') == $name) {
            $summary = [];
            $summary[] = $this->t('Formatter Template: @template', ['@template' => $title]);
            break;
          }
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    $field_type = $this->fieldDefinition->getType();
    if (!$this->isReference($field_type)) {
      return;
    }
    parent::prepareView($entities_items);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $field_type = $this->fieldDefinition->getType();
    $settings = $this->getSettings();
    $data_items = $items;
    if ($this->isReference($field_type)) {
      $data_items = $this->getEntitiesToView($items, $langcode);
    }
    $elements = fft_field_formatter_render($entity, $field_type, $data_items, $settings, $langcode);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $view = parent::view($items, $langcode);
    if ($this->settings['reset'] === '1') {
      unset($view['#theme']);
    }
    return $view;
  }

}
