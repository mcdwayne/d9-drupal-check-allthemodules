<?php

namespace Drupal\flexfield\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


abstract class FlexFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  protected $flexFieldManager = null;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [

    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @param array $flexfield_manager
   *   The FlexField Plugin Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FlexFieldTypeManagerInterface $flexfield_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.flexfield_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Get the flexfield items for this field.
   */
  public function getFlexFieldItems() {
    return $this->flexFieldManager->getFlexFieldItems($this->fieldDefinition->getSettings());
  }

}
