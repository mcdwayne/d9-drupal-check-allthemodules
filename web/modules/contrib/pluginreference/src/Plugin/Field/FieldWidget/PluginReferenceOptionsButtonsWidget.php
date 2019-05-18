<?php

namespace Drupal\pluginreference\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'plugin_reference_options_buttons' widget.
 *
 * @FieldWidget(
 *   id = "plugin_reference_options_buttons",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "plugin_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class PluginReferenceOptionsButtonsWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Plugin Manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    PluginManagerInterface $plugin_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $configuration['field_definition'];
    $target_type = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');

    return new static(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.' . $target_type)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);

    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = [key($options)];
    }

    if ($this->multiple) {
      $element += [
        '#type' => 'checkboxes',
        '#default_value' => $selected,
        '#options' => $options,
      ];
    }
    else {
      $element += [
        '#type' => 'radios',
        // Radio buttons need a scalar value. Take the first default value, or
        // default to NULL so that the form element is properly recognized as
        // not having a default value.
        '#default_value' => $selected ? reset($selected) : NULL,
        '#options' => $options,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(FieldableEntityInterface $entity) {
    $options = [];
    foreach ($this->pluginManager->getDefinitions() as $plugin_type_id => $plugin_definition) {
      $options[$plugin_type_id] = isset($plugin_definition['label']) ? $plugin_definition['label'] : $plugin_type_id;
    }

    // Add an empty option if the widget needs one.
    if ($empty_label = $this->getEmptyLabel()) {
      $options = ['_none' => $empty_label] + $options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return $this->t('N/A');
    }
  }

}
