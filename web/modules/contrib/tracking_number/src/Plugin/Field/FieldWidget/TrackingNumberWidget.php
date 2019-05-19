<?php

namespace Drupal\tracking_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tracking_number\Plugin\TrackingNumberTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'tracking_number' widget.
 *
 * @FieldWidget(
 *   id = "tracking_number",
 *   module = "tracking_number",
 *   label = @Translation("Tracking number field"),
 *   field_types = {
 *     "tracking_number"
 *   }
 * )
 */
class TrackingNumberWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The tracking number type manager service.
   *
   * @var \Drupal\tracking_number\Plugin\TrackingNumberTypeManager
   */
  protected $trackingNumberTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, TrackingNumberTypeManager $tracking_number_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->trackingNumberTypeManager = $tracking_number_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('plugin.manager.tracking_number_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Set up a grouping element for our number and type fields.
    $element += [
      '#type' => 'fieldset',
      '#element_validate' => [
        [get_class($this), 'validate'],
      ],
      '#attributes' => [
        'class' => [
          'tracking-number',
          'container-inline',
        ],
      ],
    ];

    // Tracking number value.
    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#title_display' => 'invisible',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#maxlength' => 255,
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => [
          'tracking-number-value',
        ],
        'placeholder' => $this->t('Number'),
      ],
    ];

    // Tracking number type.
    $element['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#title_display' => 'invisible',
      '#options' => $this->getTypeOptions(),
      '#empty_option' => $this->t('- Type -'),
      '#default_value' => isset($items[$delta]->type) ? $items[$delta]->type : NULL,
      '#attributes' => ['class' => ['tracking-number-type']],
    ];

    return $element;
  }

  /**
   * Returns an array of the tracking number type options for the widget.
   *
   * This list is sourced from all available TrackingNumberType plugins.
   *
   * @return array
   *   The array of tracking number type options for the widget.
   *
   * @see Drupal\tracking_number\Plugin\TrackingNumberTypeManager
   */
  protected function getTypeOptions() {
    $options = [];

    foreach ($this->trackingNumberTypeManager->getDefinitions() as $id => $definition) {
      $options[$id] = $definition['label'];
    }

    // Aphabetize.
    asort($options);

    return $options;
  }

  /**
   * Form element validation callback for the tracking number widget.
   */
  public static function validate($element, FormStateInterface $form_state, $form) {
    // If the number was provided, ensure the type was also specified.
    $number_is_set = isset($element['value']['#value']) && $element['value']['#value'] !== '';
    $type_is_set = isset($element['type']['#value']) && $element['type']['#value'] !== '';
    if ($number_is_set && !$type_is_set) {
      $form_state->setError($element['type'], t('Please select a tracking number type.'));
    }
  }

}
