<?php

namespace Drupal\gmap_polygon_field\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gmap_polygon_field\Services\ConfigService;
use Drupal\token\Token;

/**
 * Controller routines for demo page.
 */
class SamplePageController extends ControllerBase {
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
  public function __construct(ConfigService $config, Token $token) {
    $this->config = $config;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gmap_polygon_field.config'),
      $container->get('token')
    );
  }
  /**
   * Content of demo page.
   */
  public function content() {
  	if (empty($this->config->get('gmap_polygon_field_api_key', ''))) {
	  return array(
	    '#type' => 'markup',
		'#markup' => $this->t('You have to specify Google Maps API key first. Go to Configuration -> Content authoring -> GMap Polygon Field.'),
	  );
	}

	$content = array(
		'#suffix' => '<div class="gmap_polygon_field_map gmap-editable"></div>',
		'#attributes' => array('class' => array('gmap_polygon_field')),
		'#attached' => array(
		  'library' => array('gmap_polygon_field/gmap-polygon-field'),
		  'drupalSettings' => array(
		  	'gmap_polygon_field' => array(
	          'strokeColor' => $this->token->replace($this->config->get('gmap_polygon_field_stroke_color')),
	          'strokeOpacity' => $this->token->replace($this->config->get('gmap_polygon_field_stroke_opacity')),
	          'strokeWeight' => $this->token->replace($this->config->get('gmap_polygon_field_stroke_weight')),
	          'fillColor' => $this->token->replace($this->config->get('gmap_polygon_field_fill_color')),
	        ),
		  ),
		),
	);
	$content += array(
		'#type' => 'textfield',
		'#attributes' => array('class' => array('gmap_polygon_field_poly_text')),
		'#size' => 70,
	);
	return $content;
  }
}
