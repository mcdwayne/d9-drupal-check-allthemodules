<?php

namespace Drupal\fotorama_gallery\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Plugin implementation of the 'fotorama_gallery display' formatter.
 *
 * @FieldFormatter(
 *   id = "fotorama_gallery",
 *   label = @Translation("Fotorama"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FotoramaGalleryFormatter extends ImageFormatter {

  /**
   * The Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FotoramaGalleryFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory services.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [];
    $selects_fields
      = \Drupal::config('fotorama_gallery.settings')->get('SelectFields');
    $check_box_fields
      = \Drupal::config('fotorama_gallery.settings')->get('CheckBoxFields');
    $dimensions_fields
      = \Drupal::config('fotorama_gallery.settings')->get('NumberFields');

    $all_fields = $selects_fields + $check_box_fields + $dimensions_fields;

    /* construct $default_settings array,
     * $default_settings['field-group']['key_field'] = 'defaultvalue'
     */
    foreach ($all_fields as $field) {
      $default_settings[$field['group']][$field['key']] = $field['defaultvalue'];

      // Add percent field for all dimensions fields.
      if ($field['group'] == 'dimensions') {
        $default_settings['dimensions']['percent_' . $field['key']] = FALSE;
      }
    }
    // Specials fields.
    $default_settings['dimensions']['ratio'] = '';

    return $default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['image_link']);

    $url_options = ['attributes' => ['target' => '_blank']];

    // Field groups.
    $element['dimensions'] = [
      '#type' => 'details',
      '#title' => $this->t('Dimensions'),
      '#description' => Link::fromTextAndUrl(
        $this->t('Documentation: Dimensions'),
        Url::fromUri('http://fotorama.io/customize/dimensions/', $url_options)
      ),
    ];
    $element['others'] = [
      '#type' => 'details',
      '#title' => $this->t('Others'),
    ];
    $element['autoplay'] = [
      '#type' => 'details',
      '#title' => $this->t('Autoplay'),
    ];
    $element['navigation'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation'),
    ];
    $element['transition'] = [
      '#type' => 'details',
      '#title' => $this->t('Transition'),
    ];

    // Specials fields.
    $element['dimensions']['ratio'] = [
      '#type' => 'textfield',
      '#open' => 1,
      '#title' => $this->t('Ratio'),
      '#size' => 10,
      '#default_value' => $this->getSetting('dimensions')['ratio'],
    ];

    // Common fields.
    $this->settingsFormSelectsFields($element, $url_options);
    $this->settingsFormCheckBoxFields($element, $url_options);
    $this->settingsFormNumberFields($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = "Fotorama Gallery Settings";
    $summary += parent::settingsSummary();

    // Specials fields.
    $value = $this->getSetting('dimensions')['ratio'];
    if (!empty($value)) {
      $summary[] = $this->t('data-ratio: @value', [
        '@value' => $value,
      ]
      );
    }

    // Common fields.
    $this->settingsSummarySelectsFields($summary);
    $this->settingsSummaryNumberFields($summary);
    $this->settingsSummaryCheckBoxFields($summary);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $elements['#theme'] = 'fotorama_gallery_field';

    // Common fields.
    $elements['attributes'] = $this->viewElementsSelectsFields() + $this->viewElementsNumberFields() + $this->viewElementsCheckBoxFields();

    // Specials fields.
    if (!empty($this->getSetting('dimensions')['ratio'])) {
      $elements['attributes']['data-ratio'] = $this->getSetting('dimensions')['ratio'];
    }

    return $elements;
  }

  /**
   * Fill $element with the select fields information from the settings.
   *
   * @param array $element
   *   Form elements.
   * @param array $url_options
   *   Url attributes.
   */
  private function settingsFormSelectsFields(array &$element, array $url_options) {
    $selects_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('SelectFields');
    foreach ($selects_fields as $field) {
      $element[$field['group']][$field['key']] = [
        '#type' => 'select',
        '#title' => $field['data'],
        '#options' => $field['options'],
        '#default_value' => $this->getSetting($field['group'])[$field['key']],
        '#description' => Link::fromTextAndUrl(
          $this->t('Documentation: @field', ['@field' => $field['data']]),
          Url::fromUri($field['documentation'], $url_options)
        ),
      ];
    }
  }

  /**
   * Fill $element with the Checkbox fields information from the settings.
   *
   * @param array $element
   *   Form elements.
   * @param array $url_options
   *   Url attributes.
   */
  private function settingsFormCheckBoxFields(array &$element, array $url_options) {
    $check_box_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('CheckBoxFields');

    foreach ($check_box_fields as $field) {
      $element[$field['group']][$field['key']] = [
        '#type' => 'checkbox',
        '#title' => $field['data'],
        '#default_value' => $this->getSetting($field['group'])[$field['key']],
        '#description' => Link::fromTextAndUrl(
          $this->t('Documentation: @field', ['@field' => $field['data']]),
          Url::fromUri($field['documentation'], $url_options)
        ),
      ];
    }
  }

  /**
   * Fill $element with the Dimension fields information from the settings.
   *
   * @param array $element
   *   Form elements.
   */
  private function settingsFormNumberFields(array &$element) {
    $dimensions_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('NumberFields');
    foreach ($dimensions_fields as $field) {
      $element['dimensions'][$field['key']]
        = $this->fieldDimensionsNumberBuilder($field['data'], $field['key']);
      $element['dimensions'][$field['percent']]
        = $this->fieldDimensionsCheckBoxBuilder($field['percent']);
    }
  }

  /**
   * Construct the summary for each select field.
   *
   * @param array $summary
   *   Summary array to be fill with the field information.
   */
  private function settingsSummarySelectsFields(array &$summary) {
    $selects_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('SelectFields');
    foreach ($selects_fields as $field) {
      $value = $this->getSetting($field['group'])[$field['key']];
      if ($value != $field['defaultvalue']) {
        $summary[] = $this->t('@label: @value', [
          '@label' => $field['data'],
          '@value' => $field['options'][$value],
        ]
        );
      }

    }
  }

  /**
   * Construct the summary for each dimension field.
   *
   * @param array $summary
   *   Summary array to be fill with the field information.
   */
  private function settingsSummaryNumberFields(array &$summary) {
    $dimensions_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('NumberFields');
    foreach ($dimensions_fields as $field) {
      $value = $this->getSetting('dimensions')[$field['key']];
      $value_formatted = $this->getNumberFieldsValuePercent($field['key'], $field['percent']);
      if (!empty($value)) {
        $summary[] = $this->t('@label: @value', [
          '@label' => $field['data'],
          '@value' => $value_formatted,
        ]
        );
      }
    }
  }

  /**
   * Construct the summary for each checkbox field.
   *
   * @param array $summary
   *   Summary array to be fill with the field information.
   */
  private function settingsSummaryCheckBoxFields(array &$summary) {
    $check_box_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('CheckBoxFields');
    foreach ($check_box_fields as $field) {
      $value = $this->getSetting($field['group'])[$field['key']];
      if ($value != $field['defaultvalue']) {
        $value = ($value) ? 'true' : 'false';
        $summary[] = $this->t('@label: @value', [
          '@label' => $field['data'],
          '@value' => $value,
        ]
        );
      }
    }
  }

  /**
   * Get the value for each Select field.
   *
   * @return array
   *   Array with value for each field.
   */
  private function viewElementsSelectsFields() {
    $selects_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('SelectFields');
    $attributes = [];
    foreach ($selects_fields as $field) {
      $value = $this->getSetting($field['group'])[$field['key']];
      if (array_search($field['options'][$value], $field['options']) != $field['defaultvalue']) {
        $attributes[$field['data']] = $field['options'][$value];
      }

    }
    return $attributes;
  }

  /**
   * Get the value for each Checkbox field.
   *
   * @return array
   *   Array with value for each field.
   */
  private function viewElementsCheckBoxFields() {
    $check_box_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('CheckBoxFields');
    $attributes = [];
    foreach ($check_box_fields as $field) {
      $value = $this->getSetting($field['group'])[$field['key']];
      $value_formatted = ($value) ? 'true' : 'false';
      $value_boolean = ($value) ? 1 : 0;
      if ($field['defaultvalue'] != $value_boolean) {
        $attributes[$field['data']] = $value_formatted;
      }
    }
    return $attributes;
  }

  /**
   * Get the value for each dimension field.
   *
   * @return array
   *   Array with value for each field.
   */
  private function viewElementsNumberFields() {
    $dimensions_fields
      = $this->configFactory->get('fotorama_gallery.settings')->get('NumberFields');
    $attributes = [];
    foreach ($dimensions_fields as $field) {
      $value = $this->getSetting('dimensions')[$field['key']];
      $value_formatted = $this->getNumberFieldsValuePercent($field['key'], $field['percent']);
      if ($value) {
        $attributes[$field['data']] = $value_formatted;
      }
    }
    return $attributes;
  }

  /**
   * Return a array with a field number settings.
   *
   * @param string $label
   *   Label of the field.
   * @param string $field_key
   *   Key of the field to construct.
   *
   * @return array
   *   The array of field number settings.
   */
  private function fieldDimensionsNumberBuilder($label, $field_key) {
    return [
      '#title' => $label,
      '#type' => 'number',
      '#size' => 4,
      '#default_value' => $this->getSetting('dimensions')[$field_key],
    ];
  }

  /**
   * Return a array with a field checkbox settings.
   *
   * @param string $field_key
   *   Key of the field.
   *
   * @return array
   *   Array of checkbox settings.
   */
  private function fieldDimensionsCheckBoxBuilder($field_key) {
    return [
      '#type' => 'checkbox',
      '#title' => $this->t('Check if the value is a percentage'),
      '#default_value' => $this->getSetting('dimensions')[$field_key],
    ];
  }

  /**
   * Get the correct format of the field value.
   *
   * @param string $field_key
   *   Key of the field.
   * @param string $field_percent
   *   Key of the field.
   *
   * @return string
   *   Value of the field with the correct format.
   */
  private function getNumberFieldsValuePercent($field_key, $field_percent) {

    $value = $this->getSetting('dimensions')[$field_key];
    if (array_key_exists($field_percent, $this->getSetting('dimensions')) && $this->getSetting('dimensions')[$field_percent]) {
      return $value . '%';
    }
    else {
      return $value;
    }
  }

}
