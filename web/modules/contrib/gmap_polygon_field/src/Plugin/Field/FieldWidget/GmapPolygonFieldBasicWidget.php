<?php

namespace Drupal\gmap_polygon_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gmap_polygon_field\Services\ConfigService;
use Drupal\token\Token;

/**
 * Plugin implementation of the widget.
 *
 * @FieldWidget(
 *   id = "gmap_polygon_field_basic_widget",
 *   label = @Translation("GMap Polygon Field Basic Widget"),
 *   field_types = {
 *     "gmap_polygon_field"
 *   }
 * )
 */

class GmapPolygonFieldBasicWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface {
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

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigService $config, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    
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
      $configuration['third_party_settings'],
      $container->get('gmap_polygon_field.config'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('gmap_polygon_field.settings');
  	$value = isset($items[$delta]->polyline) ? $items[$delta]->polyline : '';
  	$node = $form_state->getFormObject()->getEntity();
  	
  	$widget = array();
  	$widget['#delta'] = $delta;
  	$widget = array(
		'#suffix' => '<div class="gmap_polygon_field_map gmap-editable"></div>',
		'#attributes' => array('class' => array('gmap_polygon_field')),
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
	$widget += array(
		'#type' => 'textfield',
		'#attributes' => array('class' => array('gmap_polygon_field_poly_text')),
		'#size' => 70,
		'#default_value' => $value,
	);

	$element['polyline'] = $widget;
  	return $element;
  }
}
