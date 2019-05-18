<?php

namespace Drupal\gridstack_field\Plugin\Field\FieldWidget;


use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\gridstack_field\GridstackFieldHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'postal_code' widget.
 *
 * @FieldWidget(
 *   id = "gridstack_field_widget",
 *   module = "gridstack_field",
 *   label = @Translation("Gridstack field"),
 *   field_types = {
 *     "gridstack_field"
 *   }
 * )
 */
class GridstackFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
 * {@inheritdoc}
 */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getFieldSettings();


    // Converting options to boolean type for preventing issues
    // with incorrect types.
    $options = GridstackFieldHelper::getOptions('bool');
    foreach ($options as $option) {
      $settings[$option] = (bool) $settings[$option];
    }

    // Converting options to int type for preventing issues
    // with incorrect types.
    $options = GridstackFieldHelper::getOptions('int');
    foreach ($options as $option) {
      $settings[$option] = intval($settings[$option]);
    }
    // Pass settings into script.
    $element['#attached']['drupalSettings']['gridstack_field']['settings'] = $settings;

    // Add Backbone, Underscore and Gridstack libraries.
    $element['#attached']['library'][] = 'gridstack_field/gridstack_field.library';

    $value = $items->getValue();
    $element['items'] = [
      '#markup' => '<div class="gridstack-items"><div class="grid-stack"></div></div>',
    ];
    $element['gridstack_group'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => Html::escape(isset($element['#title']) ? $element['#title'] : ''),
      '#description' => Html::escape(isset($element['#description']) ? $element['#description'] : ''),
    ];
    $element['gridstack_group']['add_item'] = [
      '#type' => 'button',
      '#button_type' => 'button',
      '#value' => $this->t('Add item'),
      '#executes_submit_callback' => FALSE,
    ];
    $element['gridstack_group']['gridstack_autocomplete'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Node'),
      '#maxlength' => 60,
      '#target_type' => 'node',
    ];
    $element['json'] = [
      '#type' => 'textfield',
      '#default_value' => isset($value[0]['json']) ? $value[0]['json'] : '',
      '#maxlength' => 2048,
      '#size' => 60,
    ];

    return $element;
  }
}
