<?php

namespace Drupal\formatter_field\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'formatter' field widget.
 *
 * @FieldWidget(
 *   id = "formatter_field_formatter",
 *   label = @Translation("Formatter"),
 *   field_types = {"formatter_field_formatter"},
 *   multiple_values = TRUE,
 * )
 */
class FormatterWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a new ModerationStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   Formatter plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, FormatterPluginManager $formatter_manager, EntityFieldManagerInterface $field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->formatterManager = $formatter_manager;
    $this->fieldManager = $field_manager;
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.field.formatter'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // This setting may not be configured yet when the widget is rendered for
    // default value form.
    if (!$this->fieldDefinition->getSetting('field')) {
      return [];
    }

    $target_field_name = $this->fieldDefinition->getSetting('field');

    $definitions = $this->fieldManager->getFieldDefinitions(
      $this->fieldDefinition->getTargetEntityTypeId(),
      $this->fieldDefinition->getTargetBundle()
    );

    if (!isset($definitions[$target_field_name])) {
      drupal_set_message($this->t('Field %field_name does not exist.', ['%field_name' => $target_field_name]), 'warning');
      return [];
    }

    $target_definition = $definitions[$this->fieldDefinition->getSetting('field')];

    $element['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Display settings for @field_name field', ['@field_name' => $target_definition->getLabel()]),
      '#open' => TRUE,
    ];

    // Find all available formatters for this field type.
    $options = $this->formatterManager->getOptions($target_definition->getType());
    $applicable_options = ['' => $this->t('- Hidden -')];
    foreach ($options as $option => $label) {
      $plugin_class = DefaultFactory::getPluginClass($option, $this->formatterManager->getDefinition($option));
      if ($plugin_class::isApplicable($target_definition) && $option != 'formatter_field_from') {
        $applicable_options[$option] = $label;
      }
    }

    // Consider first available option as default formatter.
    $type = '';
    $settings = [];
    if (isset($items[0]->type)) {
      $type = $items[0]->type;
      $settings = unserialize($items[0]->settings);
    };

    $id = 'formatter-settings-form-' . $target_field_name;
    $element['container']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Formatter'),
      '#options' => $applicable_options,
      '#ajax' => [
        'callback' => [$this, 'settingsFormAjax'],
        'wrapper' => $id,
        'effect' => 'fade',
      ],
      '#default_value' => $type,
    ];

    // Formatter type may come from ajax request.
    $field_name = $this->fieldDefinition->getName();
    $value = $form_state->getValue($field_name);
    // Form structure is different on field settings edit form.
    if (!$value && $default_value_input = $form_state->getValue('default_value_input')) {
      $value = $default_value_input[$field_name];
    }
    if (isset($value['container']['type'])) {
      $type = $value['container']['type'];
    }

    if ($type) {
      $options = [
        'field_definition' => $target_definition,
        'configuration' => [
          'type' => $type,
          'settings' => $settings,
          'label' => '',
          'weight' => 0,
        ],
        'view_mode' => '_custom',
      ];
      $formatter = $this->formatterManager->getInstance($options);

      $element['container']['settings'] = $formatter->settingsForm($form, $form_state);
    }

    $element['container']['settings']['#prefix'] = sprintf('<div id="%s">', $id);
    $element['container']['settings']['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Ajax handler for settings form.
   */
  public function settingsFormAjax($form, FormStateInterface $form_state) {
    $parent_element = $form['#form_id'] == 'field_config_edit_form' ?
      'default_value' : $this->fieldDefinition->getName();
    return $form[$parent_element]['widget']['container']['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $settings = isset($values['container']['settings']) ? $values['container']['settings'] : [];
    return [
      'type' => $values['container']['type'],
      'settings' => serialize($settings),
    ];
  }

}
