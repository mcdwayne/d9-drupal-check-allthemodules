<?php

namespace Drupal\flexfield\Plugin;

use Drupal\flexfield\Plugin\Field\FieldType\FlexItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Flexfield Type plugins.
 */
abstract class FlexFieldTypeBase extends PluginBase implements FlexFieldTypeInterface {

  /**
   * The name of the flexfield item
   * @var string
   */
  protected $name = 'value';

  /**
   * The max length of the flexfield item database column
   * @var integer
   */
  protected $max_length = 255;

  /**
   * An array of widget settings
   * @var array
   */
  protected $widget_settings = [];

  /**
   * An array of formatter settings
   * @var array
   */
  protected $formatter_settings = [];

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings() {
    return [
      'label' => '',
      'description' => '',
      'required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings() {
    return [];
  }

  /**
   * Construct a FlexFieldType plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Intialize properties based on configuration.
    $this->name = isset($this->configuration['name']) ? $this->configuration['name'] : 'value';
    $this->max_length = isset($this->configuration['max_length']) ? $this->configuration['max_length'] : 255;
    $this->widget_settings = isset($this->configuration['widget_settings']) ? $this->configuration['widget_settings'] : [];
    $this->formatter_settings = isset($this->configuration['formatter_settings']) ? $this->configuration['formatter_settings'] : [];

    // We want to default the label to the column name so we do that before the
    // merge and only if it's unset since a value of '' may be what the user
    // wants for no label
    if (!isset($this->widget_settings['label'])) {
      $this->widget_settings['label'] = ucfirst(str_replace(['-', '_'], ' ', $this->name));
    }

    // Merge defaults
    $this->widget_settings = $this->widget_settings + self::defaultWidgetSettings();
    $this->formatter_settings = $this->formatter_settings + self::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Prep the element base properties. Implementations of the plugin can
    // override as necessary or just set #type and be on their merry way.
    return [
      '#title' => $this->widget_settings['label'],
      '#description' => $this->widget_settings['description'],
      '#default_value' => isset($items[$delta]->{$this->name}) ? $items[$delta]->{$this->name} : NULL,
      '#required' => $form_state->getBuildInfo()['base_form_id'] == 'field_config_form' ? FALSE : $this->widget_settings['required'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state) {

    // Some table columns containing raw markup.
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->widget_settings['label'],
      '#required' => TRUE,
    ];

    // Some table columns containing raw markup.
    $element['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => $this->widget_settings['required'],
    ];

    // Some table columns containing raw markup.
    $element['description'] = [
      '#type' => 'textarea',
      '#title' => t('Widget Description'),
      '#rows' => 2,
      '#default_value' => $this->widget_settings['description'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(FlexItem $item) {
    return $item->{$this->name};
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->widget_settings['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings() {
    return $this->widget_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatterSettings() {
    return $this->formatter_settings;
  }
}
