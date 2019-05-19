<?php

namespace Drupal\svg_maps\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\svg_maps\SvgMapsTypeManager;

/**
 * Plugin implementation of the 'Svg map detail' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_maps_global_integer",
 *   module = "svg_maps",
 *   label = @Translation("Global Map"),
 *   field_types = {
 *     "svg_maps_integer"
 *   }
 * )
 */
class SvgMapsGlobalIntegerFormatter extends IntegerFormatter implements SvgMapsFormatterInterface, ContainerFactoryPluginInterface {

  use SvgMapsFormatterTrait{
    SvgMapsFormatterTrait::__construct as private __stConstruct;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, SvgMapsTypeManager $svgMapsPlugin) {
    $this->__stConstruct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $svgMapsPlugin);
  }

  /**
   * {@inheritdoc}
   */
  public static function isGlobal() {
    return TRUE;
  }

}
