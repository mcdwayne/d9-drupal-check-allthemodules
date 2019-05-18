<?php

namespace Drupal\gmap_polygon_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gmap_polygon_field\Services\ConfigService;
use Drupal\token\Token;

/**
 * Plugin implementation of the formatter.
 *
 * @FieldFormatter(
 *   id = "gmap_polygon_field_basic_formatter",
 *   label = @Translation("GMap Polygon Field Basic Formatter"),
 *   field_types = {
 *     "gmap_polygon_field"
 *   }
 * )
 */
class GmapPolygonFieldBasicFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
	/**
	 * The config service
	 *
	 * @var \Drupal\gmap_polygon_field\Services\ConfigService
	 */
	protected $config;

	/**
	 * The token service
	 *
	 * @var \Drupal\token\Token
	 */
	protected $token;

	/**
	 * {@inheritdoc}
	 */
	public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigService $config, Token $token) {
	  parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

	  $this->config = $config;
	  $this->token = $token;
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
		  $container->get('gmap_polygon_field.config'),
		  $container->get('token')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function viewElements(FieldItemListInterface $items, $langcode) {
	  $element = array();

	  foreach ($items as $delta => $item) {
	  	$node = $item->getEntity();
	    $element[$delta] = array(
	      '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => array('class' => 'gmap_polygon_field_map', 'data-polyline-encoded' => $item->polyline),
          '#attached' => array(
		    'library' => array('gmap_polygon_field/gmap-polygon-field'),
		    'drupalSettings' => array(
		  	  'gmap_polygon_field' => array(
	            'strokeColor' => $this->token->replace($this->config->get('gmap_polygon_field_stroke_color'), array('node' => $node)),
	            'strokeOpacity' => $this->token->replace($this->config->get('gmap_polygon_field_stroke_opacity'), array('node' => $node)),
	            'strokeWeight' => $this->token->replace($this->config->get('gmap_polygon_field_stroke_weight'), array('node' => $node)),
	            'fillColor' => $this->token->replace($this->config->get('gmap_polygon_field_fill_color'), array('node' => $node)),
	          ),
		    ),
		  ),
	    );
	  }
	  return $element;
	}
}
