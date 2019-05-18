<?php

namespace Drupal\product_choice\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'entity reference product choice' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_product_choice",
 *   label = @Translation("Product choice label"),
 *   description = @Translation("Display the referenced product choice label."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceProductChoiceFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];

    // Fall back to field settings by default.
    $settings['output_format'] = 'label';
    $settings['blank_values_display'] = '';
    $settings['blank_custom_text'] = '';
    $settings['image_style_name'] = 'thumbnail';

    return $settings;
  }

  /**
   * Gets the available output format options.
   *
   * @return array|string
   *   A list of output formats. Each entry is keyed by the machine name of the
   *   format. The value is the description of the format.
   */
  protected function getOutputFormats() {

    $formats = [
      'label' => $this->t('Display the default label value.'),
      'shortened' => $this->t('Display the shortened label value.'),
      'formatted' => $this->t('Display the formatted label value.'),
      'icon' => $this->t('Display the icon image.'),
    ];

    return $formats;
  }

  /**
   * Gets the available output format options.
   *
   * @return array|string
   *   A list of output formats. Each entry is keyed by the machine name of the
   *   display option. The value is the description of the display option.
   */
  protected function getBlankValueDisplayOptions() {
    $options = [
      'blank' => $this->t('Allow blank values.'),
      'label' => $this->t('Display the default label value.'),
      'NA' => $this->t('Display N/A in place of blanks.'),
      'custom' => $this->t('Display custom text for blanks.'),
    ];

    return $options;
  }

  /**
   * Gets the default text for a blank label.
   *
   * @return string
   *   Text that should be displayed in place of a blank label
   */
  protected function getBlankDefault($default_label_text = '') {
    $blank_values_display = $this->getSetting('blank_values_display');

    if ($blank_values_display == 'label') {
      return $default_label_text;
    }

    if ($blank_values_display == 'NA') {
      return 'N/A';
    }

    if ($blank_values_display == 'custom') {
      return $this->getSetting('blank_custom_text');
    }

    return '';
  }

/**
 * Gets an array of image styles suitable for using as select list options.
 *
 * @return
 *   Array of image styles both key and value are set to style name.
 */
function getImageStyleOptions() {
  $styles = ImageStyle::loadMultiple();
  $options = array();

  foreach ($styles as $name => $style) {
    $options[$name] = $style->label();
  }

  if (empty($options)) {
    $options[''] = t('No defined styles');
  }
  return $options;
}

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $field_name = $this->fieldDefinition->getName();

    $form['output_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $this->getSetting('output_format'),
      '#options' => $this->getOutputFormats(),
    ];
    $form['blank_values_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display of blank values'),
      '#default_value' => $this->getSetting('blank_values_display'),
      '#options' => $this->getBlankValueDisplayOptions(),
      '#states' => [
        'visible' => [
          ['select[name="fields[' . $field_name . '][settings_edit_form][settings][output_format]"]' => ['value' => 'shortened']],
          'or',
          ['select[name="fields[' . $field_name . '][settings_edit_form][settings][output_format]"]' => ['value' => 'formatted']],
        ],
      ],
    ];
    $form['image_style_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon image style'),
      '#default_value' => $this->getSetting('image_style_name'),
      '#options' => $this->getImageStyleOptions(),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][output_format]"]' => 
            ['value' => 'icon'],
        ],
      ],
    ];
    $form['blank_custom_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom text for blank values'),
      '#default_value' => $this->getSetting('blank_custom_text'),
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][blank_values_display]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $formats = $this->getOutputFormats();
    $output_format = $this->getSetting('output_format');
    $summary[] = $formats[$output_format];

    if (($output_format == 'shortened') || ($output_format == 'formatted')) {
      $blank_setting = $this->getSetting('blank_values_display');
      if ($blank_setting == 'custom') {
        $summary[] = $this->t('Display custom text for blanks: @custom_text', [
          '@custom_text' => $this->getSetting('blank_custom_text'),
        ]);
      }
      else {
        $blank_display_options = $this->getBlankValueDisplayOptions();
        $summary[] = $blank_display_options[$blank_setting];
      }
    }
    elseif ($output_format == 'icon') {
      $image_styles = $this->getImageStyleOptions();
      // Unset possible 'No defined styles' option.
      unset($image_styles['']);
      // Styles could be lost because of enabled/disabled modules that defines
      // their styles in code.
      $image_style_setting = $this->getSetting('image_style_name');
      if (isset($image_styles[$image_style_setting])) {
        $preview_image_style = t('Preview image style: @style', array('@style' => $image_styles[$image_style_setting]));
      }
      else {
        $preview_image_style = t('No preview');
      }

      array_unshift($summary, $preview_image_style);      
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_format = $this->getSetting('output_format');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {

      if ($output_format == 'icon') {
        // Avoid error of getFileUri() on null.
        if (!isset($entity->icon->entity)) {
          $elements[$delta] = [
            '#plain_text' => $this->t('@label', ['@label' => $entity->getLabel()]),
          ];
        }
        else {
          $elements[$delta] = [
            '#theme' => 'image_style',
            '#style_name' => $this->getSetting('image_style_name'),
            '#uri' => $entity->icon->entity->getFileUri(),
            '#title' => $this->t('@label', ['@label' => $entity->getLabel()]),
          ];
        }
      }
      elseif ($output_format == 'shortened') {
        if (!$entity->getShortened()) {
          $elements[$delta] = [
            '#plain_text' => $this->t('@label', ['@label' => $this->getBlankDefault($entity->getLabel())]),
          ];
        }
        else {
          $elements[$delta] = [
            '#plain_text' => t('@label', ['@label' => $entity->getShortened()]),
          ];
        }
      }
      elseif ($output_format == 'formatted') {
        if (!$entity->getFormattedText()) {
          $elements[$delta] = [
            '#plain_text' => $this->t('@label', ['@label' => $this->getBlankDefault($entity->getLabel())]),
          ];
        }
        else {
          $elements[$delta] = [
            '#type' => 'processed_text',
            '#text' => $entity->getFormattedText(),
            '#format' => $entity->getFormattedFormat(),
          ];
        }
      }
      else {
        $elements[$delta] = [
          '#plain_text' => $this->t('@label', ['@label' => $entity->getLabel()]),
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for product choice terms.
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'product_choice_term');
  }

}
