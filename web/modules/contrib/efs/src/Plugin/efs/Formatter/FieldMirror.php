<?php

namespace Drupal\efs\Plugin\efs\Formatter;

use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\efs\Entity\ExtraFieldInterface;
use Drupal\efs\ExtraFieldFormatterPluginBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Details element.
 *
 * @ExtraFieldFormatter(
 *   id = "field_mirror",
 *   label = @Translation("Field mirror"),
 *   description = @Translation("Field mirror formatter"),
 *   supported_contexts = {
 *     "display"
 *   }
 * )
 */
class FieldMirror extends ExtraFieldFormatterPluginBase {

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatter;

  /**
   * The language interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormatterPluginManager $formatter, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formatter = $formatter;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.field.formatter'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings(string $context) {
    $defaults = [
      'field' => NULL,
      'formatter' => NULL,
      'formatter_settings' => ['label' => 'above', 'settings' => []],
    ] + parent::defaultSettings();

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;

  }

  /**
   * Get the possible formatters of field.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The formatters of field.
   */
  public static function getFieldFormatters(array $form, FormStateInterface $form_state) {
    $field = $form_state->getValue('field_mirror_name');
    $select = $form['fields'][$field]['format']['format_settings']['settings']['formatter'];
    return $select;
  }

  /**
   * Get the field formatter settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The formatter of field settings.
   */
  public static function getFieldFormatterSettings(array $form, FormStateInterface $form_state) {
    $field = $form_state->getValue('field_mirror_name');
    $select = $form['fields'][$field]['format']['format_settings']['settings']['formatter_settings'];
    return $select;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array $build, EntityInterface $entity, EntityDisplayBase $display, string $view_mode, ExtraFieldInterface $extra_field) {
    $settings = $this->getSettings();

    $field_definitions = $display->get('fieldDefinitions');
    $field = $field_definitions[$settings['field']];
    $plugin_id = $settings['formatter'];
    $items = $entity->get($settings['field']);
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $configuration = [
      'field_definition' => $field,
      'third_party_settings' => [],
      'settings' => $settings['formatter_settings']['settings'],
      'label' => $settings['formatter_settings']['label'],
      'view_mode' => $view_mode,
    ];

    $plugin = $this->formatter->createInstance($plugin_id, $configuration);

    $plugin->prepareView([$entity->id() => $items]);
    $element = $plugin->view($items, $langcode);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(EntityDisplayFormBase $view_display, array $form, FormStateInterface $form_state, ExtraFieldInterface $extra_field, string $field) {
    $form = parent::settingsForm($view_display, $form, $form_state, $extra_field, $field);

    /** @var \Drupal\Core\Entity\EntityDisplayBase $display */
    $display = $view_display->getEntity();
    $fields = $display->get('fieldDefinitions');
    $fields_options = $this->getFieldOptions($fields);
    $settings = $this->getSettings();
    $form_state->setValue('field_mirror_name', $field);

    $form['field'] = [
      '#title' => $this->t('Field'),
      '#type' => 'select',
      '#options' => $fields_options,
      '#default_value' => !empty($settings['field']) ? $settings['field'] : NULL,
      '#ajax' => [
        'callback' => [get_class($this), 'getFieldFormatters'],
        'wrapper' => 'field_mirror_field_formatters',
      ],
      '#empty_value' => '',
      '#empty_option' => $this->t('Select one field'),
    ];

    $values = $form_state->getValues();
    $field_name = !empty($values['fields'][$field]['settings_edit_form']['settings']['field']) ? $values['fields'][$field]['settings_edit_form']['settings']['field'] : $settings['field'];
    $formatters = !empty($field_name) ? $this->getFieldSelectedFormatters($field_name, $fields) : [];
    $form['formatter'] = [
      '#type' => 'select',
      '#title' => $this->t('Formatter'),
      '#options' => $formatters,
      '#default_value' => !empty($settings['formatter']) ? $settings['formatter'] : NULL,
      '#prefix' => '<div id="field_mirror_field_formatters">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [get_class($this), 'getFieldFormatterSettings'],
        'wrapper' => 'field_mirror_field_formatter_settings',
      ],
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field . '][settings_edit_form][settings][field]"]' => ['!value' => ''],
        ],
      ],
      '#empty_value' => '',
      '#empty_option' => $this->t('Select one formatter'),
    ];

    $formatter = !empty($values['fields'][$field]['settings_edit_form']['settings']['formatter']) ? $values['fields'][$field]['settings_edit_form']['settings']['formatter'] : $settings['formatter'];
    $formatter_settings = !empty($formatter) ? $this->getFieldSelectedFormatterSettings($formatter, $fields[$field_name], $settings['formatter_settings'], $form, $form_state) : [];
    $form['formatter_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#tree' => TRUE,
      '#open' => TRUE,
      'label' => [
        '#type' => 'select',
        '#title' => $this->t('Label'),
        '#options' => [
          'above' => $this->t('Above'),
          'inline' => $this->t('Inline'),
          'hidden' => $this->t('Hidden'),
          'visually_hidden' => $this->t('Visually hidden'),
        ],
        '#default_value' => !empty($settings['formatter_settings']['label']) ? $settings['formatter_settings']['label'] : NULL,
      ],
      'settings' => $formatter_settings,
      '#prefix' => '<div id="field_mirror_field_formatter_settings">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          'select[name="fields[' . $field . '][settings_edit_form][settings][formatter]"]' => ['!value' => ''],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Get the field options.
   *
   * @param array $fields
   *   The array of fields.
   *
   * @return array
   *   The select options.
   */
  protected function getFieldOptions(array $fields) {
    $options = [];
    foreach ($fields as $field) {
      if ($field instanceof FieldConfig) {
        $options[$field->get('field_name')] = $field->label();
      }
    }
    return $options;
  }

  /**
   * Get the field selected formatter options.
   *
   * @param string $field_name
   *   The field name.
   * @param array $fields
   *   The list of fields.
   *
   * @return array
   *   The selected formatter options.
   */
  protected function getFieldSelectedFormatters(string $field_name, array $fields) {
    $options = [];
    $field = $fields[$field_name];
    $field_type = $field->get('field_type');
    $definitions = $this->formatter->getDefinitions();
    foreach ($definitions as $id => $def) {
      if (in_array($field_type, $def['field_types'])) {
        $options[$id] = $def['label'];
      }
    }
    return $options;
  }

  /**
   * Get the settings of selected formatter.
   *
   * @param string $plugin_id
   *   The plugin id.
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field entity config.
   * @param array $settings
   *   The settings of field.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The settings of selected formatter.
   */
  protected function getFieldSelectedFormatterSettings(string $plugin_id, FieldConfig $field, array $settings, array $form, FormStateInterface $form_state) {
    $configuration = [
      'field_definition' => $field,
      'third_party_settings' => [],
      'settings' => $settings['settings'],
      'label' => $settings['label'],
      'view_mode' => '',
    ];
    $plugin = $this->formatter->createInstance($plugin_id, $configuration);
    $settings = $plugin->settingsForm($form, $form_state);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(string $context) {
    $summary = parent::settingsSummary($context);
    if ($this->getSetting('field')) {
      $field = $this->getSetting('field');
      $formatter = $this->getSetting('formatter');
      $summary[] = $this->t('Field: %components', ['%components' => $field]);
      $summary[] = $this->t('Field formatter: %components', ['%components' => $formatter]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(string $entity_type_id, string $bundle) {
    return TRUE;
  }

}
