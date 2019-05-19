<?php

namespace Drupal\svg_maps\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\svg_maps\Entity\SvgMaps;
use Drupal\svg_maps\Entity\SvgMapsEntity;
use Drupal\svg_maps\SvgMapsTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'svg_maps' widget.
 *
 * @FieldWidget(
 *   id = "svg_maps",
 *   label = @Translation("Svg map widget"),
 *   field_types = {
 *     "svg_maps_integer",
 *     "svg_maps_decimal",
 *     "svg_maps_float"
 *   }
 * )
 */
class SvgMapsWidget extends NumberWidget implements ContainerFactoryPluginInterface {

  /**
   * The Svg map plugin service.
   *
   * @var \Drupal\svg_maps\SvgMapsTypeManager
   */
  protected $svgMapsPlugin;

  /**
   * Constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param Drupal\svg_maps\SvgMapsTypeManager $svgMapsPlugin
   *   The svg maps plugin.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, SvgMapsTypeManager $svgMapsPlugin) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->svgMapsPlugin = $svgMapsPlugin;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.svg_maps.type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $elements = parent::formElement($items, $delta, $element, $form, $form_state);
    $type = $this->getFieldSetting('svg_maps_plugin');
    $plugin = $this->svgMapsPlugin->createInstance($type);

    $options = [];
    foreach($plugin->getConfiguration()['entities'] as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    $svg_maps_item = $element + [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => !empty($items[$delta]->svg_maps_item) ? $items[$delta]->svg_maps_item : NULL,
    ];

    $elements['svg_maps_item'] = $svg_maps_item;
    $elements['svg_maps_item']['#title'] = $this->t('Svg map item');
    $elements['value']['#title'] = $this->t('Svg map value');

    // Set the step for floating point and decimal numbers.
    $field_settings = $this->getFieldSettings();
    switch ($this->fieldDefinition->getType()) {
      case 'svg_maps_decimal':
        $elements['value']['#step'] = pow(0.1, $field_settings['scale']);
        break;

      case 'svg_maps_float':
        $elements['value']['#step'] = 'any';
        break;
    }

    $fieldset = [
      '#type' => 'fieldset',
      '#title' => $plugin->label(),
    ];

    return $fieldset + $elements;

  }

}
