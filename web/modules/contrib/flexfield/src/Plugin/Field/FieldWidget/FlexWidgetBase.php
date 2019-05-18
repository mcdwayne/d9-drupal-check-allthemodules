<?php

namespace Drupal\flexfield\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class FlexWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  protected $flexFieldManager = null;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => TRUE
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @param array $flexfield_manager
   *   The FlexField Plugin Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, FlexFieldTypeManagerInterface $flexfield_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->flexFieldManager = $flexfield_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Inject our flexfield plugin manager to this plugin's constructor.
    // Made possible with ContainerFactoryPluginInterface
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.flexfield_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements = [];
    $elements['#tree'] = TRUE;

    $elements['label'] = [
      '#type' => 'checkbox',
      '#title' => t('Show field label?'),
      '#default_value' => $this->getSetting('label'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Show field label?: @label', ['@label' => $this->getSetting('label') ? 'Yes' : 'No']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($this->getSetting('label')) {
      $element['#type'] = 'item';
    }
    return $element;
  }

  /**
   * Get the field storage definition.
   */
  public function getFieldStorageDefinition() {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * Get the flexfield items for this field.
   */
  public function getFlexFieldItems() {
    return $this->flexFieldManager->getFlexFieldItems($this->fieldDefinition->getSettings());
  }

}
