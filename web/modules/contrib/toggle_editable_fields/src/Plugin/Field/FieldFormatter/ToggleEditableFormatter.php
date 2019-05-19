<?php

namespace Drupal\toggle_editable_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\toggle_editable_fields\Form\AjaxToggleForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ranking' formatter.
 *
 * @FieldFormatter(
 *   id = "toggle_editable_formatter",
 *   label = @Translation("Toggle Editable Formatter"),
 *   field_types = {
 *     "boolean"
 *   }
 * )
 */
class ToggleEditableFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a StringFormatter instance.
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
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ClassResolverInterface $class_resolver, FormBuilderInterface $form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->classResolver = $class_resolver;
    $this->formBuilder = $form_builder;
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
      $container->get('class_resolver'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'on' => 'On',
      'off' => 'Off',
      'size' => 'small',
      'onstyle' => 'success',
      'offstyle' => 'default',
      'height' => NULL,
      'width' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * Return all sizes options for boostrapToggle plugin.
   *
   * @return array
   *   A list of options to be use with boostrapToggle plugin.
   */
  public function bootstrapToggleSizesOptions() {
    return [
      'large' => $this->t('Large'),
      'normal' => $this->t('Normal'),
      'small' => $this->t('Small'),
      'mini' => $this->t('Mini'),
    ];
  }

  /**
   * Return all styles options for boostrapToggle plugin.
   *
   * @return array
   *   A list of options to be use with boostrapToggle plugin.
   */
  public function bootstrapToggleStylesOptions() {
    return [
      'default' => $this->t('Default'),
      'primary' => $this->t('Primary'),
      'success' => $this->t('Success'),
      'info' => $this->t('Info'),
      'warning' => $this->t('Warning'),
      'danger' => $this->t('Danger'),
    ];
  }

  /**
   * Retrieve setting values for given option name.
   *
   * @param string $name
   *   The name of a setting needed.
   *
   * @return string|null
   *   Thrown when the entity can't found the clicked field name.
   */
  public function getBoostrapToogleParameters($name) {
    $parameters = array_merge($this->bootstrapToggleSizesOptions(), $this->bootstrapToggleStylesOptions());
    if (!isset($parameters[$name])) {
      return NULL;
    }

    return $parameters[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    parent::settingsForm($form, $form_state);

    $elements['on'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use custom label for "On state"'),
      '#default_value' => $this->getSetting('on'),
    ];

    $elements['off'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Use custom label for "Off state"'),
      '#default_value' => $this->getSetting('off'),
    ];

    $elements['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size of toggle button'),
      '#default_value' => $this->getSetting('size'),
      '#options' => $this->bootstrapToggleSizesOptions(),
    ];

    $elements['onstyle'] = [
      '#type' => 'select',
      '#title' => $this->t('On state style of toggle button'),
      '#default_value' => $this->getSetting('onstyle'),
      '#options' => $this->bootstrapToggleStylesOptions(),
    ];

    $elements['offstyle'] = [
      '#type' => 'select',
      '#title' => $this->t('Off state style of toggle button'),
      '#default_value' => $this->getSetting('offstyle'),
      '#options' => $this->bootstrapToggleStylesOptions(),
    ];

    $elements['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Override height of toggle button'),
      '#default_value' => $this->getSetting('height'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#min' => 1,
    ];

    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Override width of toggle button'),
      '#default_value' => $this->getSetting('width'),
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#min' => 1,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('On label: @on', ['@on' => $this->getSetting('on')]);
    $summary[] = $this->t('Off label: @off', ['@off' => $this->getSetting('off')]);
    $summary[] = $this->t('Box size: @size', ['@size' => $this->getBoostrapToogleParameters($this->getSetting('size'))]);
    $summary[] = $this->t('On state style: @onstyle', ['@onstyle' => $this->getBoostrapToogleParameters($this->getSetting('onstyle'))]);
    $summary[] = $this->t('Off state style: @offstyle', ['@offstyle' => $this->getBoostrapToogleParameters($this->getSetting('offstyle'))]);
    $summary[] = $this->t('Box height: @height', ['@height' => ($this->getSetting('height') ?: $this->t('Default'))]);
    $summary[] = $this->t('Box width: @width', ['@width' => ($this->getSetting('width') ?: $this->t('Default'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      // We create an instance of element form to edit field first and,
      // initialize Form object with item definition to make a form id dynamic.
      $form_object = $this->classResolver->getInstanceFromDefinition(AjaxToggleForm::class);
      $form_object->setFieldItem($item, $this->getSettings());

      $form_state = new FormState();
      $elements[$delta] = $this->formBuilder->buildForm($form_object, $form_state);
    }

    return $elements;
  }

}
