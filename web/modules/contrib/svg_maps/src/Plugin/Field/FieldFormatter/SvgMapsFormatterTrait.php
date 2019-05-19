<?php

namespace Drupal\svg_maps\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\svg_maps\Entity\SvgMaps;
use Drupal\svg_maps\Entity\SvgMapsEntity;
use Drupal\svg_maps\SvgMapsTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for svg maps formatters.
 */
trait SvgMapsFormatterTrait {

  /**
   * The Svg map plugin service.
   *
   * @var \Drupal\svg_maps\SvgMapsTypeManager
   */
  protected $svgMapsPlugin;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param Drupal\svg_maps\SvgMapsTypeManager $svgMapsPlugin
   *   The svg map plugin.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, SvgMapsTypeManager $svgMapsPlugin) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->svgMapsPlugin = $svgMapsPlugin;
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
      $container->get('plugin.manager.svg_maps.type')
    );
  }

  /**
   * Check if items must be shown when render.
   *
   * @return bool
   *   Return if formatter is a global formatter or not.
   */
  public static function checkGlobal() {
    return static::isGlobal();
  }

  /**
   * {@inheritdoc}
   */
  abstract protected function numberFormat($number);

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getFieldSettings();
    $plugin = $this->svgMapsPlugin->createInstance($settings['svg_maps_plugin']);

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      $current = SvgMapsEntity::load($value['svg_maps_item']);
      $value = $this->formatValue($value['value'], $settings);

      $elements[$delta] = [
        '#current' => $current,
        '#value' => $value
      ];

      if(self::checkGlobal()) {
        $elements[$delta] += [
          '#theme' => $plugin->getGlobalTheme(),
          '#all' => $plugin->getConfiguration()['entities'],
        ];
      }
      else {
        $elements[$delta]['#theme'] = $plugin->getDetailedTheme();
      }
    }

    return $elements;
  }

  /**
   * Format value with prefix/suffix if needed.
   *
   * @param string $value
   *   The unformattend value.
   * @param array $settings
   *   The field settings.
   *
   * @return string
   *   The formatted value.
   */
  protected function formatValue($value, array $settings) {
    $output = $this->numberFormat($value);

    // Account for prefix and suffix.
    if ($this->getSetting('prefix_suffix')) {
      $prefixes = isset($settings['prefix']) ? array_map([
        'Drupal\Core\Field\FieldFilteredMarkup',
        'create',
      ], explode('|', $settings['prefix'])) : [''];
      $suffixes = isset($settings['suffix']) ? array_map([
        'Drupal\Core\Field\FieldFilteredMarkup',
        'create',
      ], explode('|', $settings['suffix'])) : [''];
      $prefix = (count($prefixes) > 1) ? $this->formatPlural($value, $prefixes[0], $prefixes[1]) : $prefixes[0];
      $suffix = (count($suffixes) > 1) ? $this->formatPlural($value, $suffixes[0], $suffixes[1]) : $suffixes[0];
      $output = $prefix . $output . $suffix;
    }

    return $output;
  }

}
