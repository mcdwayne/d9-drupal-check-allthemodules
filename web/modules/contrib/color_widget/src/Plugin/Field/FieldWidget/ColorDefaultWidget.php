<?php

namespace Drupal\color_widget\Plugin\Field\FieldWidget;

use Drupal\color_widget\Services\ColorHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'color_default' widget.
 *
 * @FieldWidget(
 *   id = "color_default",
 *   label = @Translation("Color Picker"),
 *   field_types = {
 *     "color_item"
 *   }
 * )
 */
class ColorDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The color helper.
   *
   * @var \Drupal\color_widget\Services\ColorHelper
   */
  protected $colorHelper;

  /**
   * ColorDefaultFormatter constructor.
   *
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ColorHelper $colorHelper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->colorHelper = $colorHelper;
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
      $container->get('color_widget.color_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $colorsArr = $this->colorHelper->convertTextareaToArray($this->fieldDefinition->getSetting('colors'));
    if (!$element['#required']) {
      $colorsArr = [NULL => $this->t('N/A')] + $colorsArr;
    }

    if (!empty($colorsArr)) {
      $defaultValue = isset($items[$delta]->value) && isset($colorsArr[$items[$delta]->value]) ? $items[$delta]->value : NULL;
      $element['value'] = $element + [
        '#type' => 'radios',
        '#options' => $colorsArr,
        '#default_value' => $defaultValue,
        '#description' => $this->t('Select a color'),
        '#attributes' => [
          'class' => ['color-picker-radio-class'],
        ],
      ];
    }

    $element['#attached']['library'][] = 'color_widget/color_widget';
    return $element;
  }
}