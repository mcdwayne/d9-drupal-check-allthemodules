<?php

namespace Drupal\color_widget\Plugin\Field\FieldFormatter;

use Drupal\color_widget\Services\ColorHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'color_id' formatter.
 *
 * @FieldFormatter(
 *   id = "color_id",
 *   module = "color_widget",
 *   label = @Translation("Color Id"),
 *   field_types = {
 *     "color_item"
 *   }
 * )
 */
class ColorIdFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The colorhelper.
   *
   * @var \Drupal\color_widget\Services\ColorHelper
   */
  protected $colorHelper;

  /**
   * ColorDefaultFormatter constructor.
   *
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ColorHelper $colorHelper) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );

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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('color_widget.color_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $item->value];
    }
    return $elements;
  }
}