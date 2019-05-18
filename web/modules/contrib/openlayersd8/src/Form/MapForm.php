<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\MapForm.
 */

namespace Drupal\openlayers\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class MapForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
		$map = array(
			'label' => 'Configuration Map',
			'entityID' => 2,
			'description' => null,
			'sources' => array(
				'1' => array(
					'id' => 1,
					'title' => 'OpenStreetMap',
					'type' => 'osm',
					'url' => 'none',
					'uuid' => 'c0bfc36b-db92-4fd9-abe6-92cf81381c04',
					'serverType' => 'none',
				)
			),
			'layers' => array(
				'59b797af-a1fe-4234-becd-f3ab1bfbef69' => array(
					'type' => 'tile',
					'source' => 1,
					'title' => 'OpenStreetMap',
					'layer' => 'none',
					'id' => 3,
					'isBase' => 1,
					'isActive' => 1,
					'opacity' => 1,
					'features' => '',
				),
			),
			'navbar' => array(
				'settings' => array(),
				'controls' => array(
					array(
						'name' => 'MapConfig',
						'machine' => 'MapConfig',
						'type' => 'custom',
						'namespace' => 'configcontrol',
						'factory' => 'openlayers',
						'tooltip' => 'R',
						'icon' => 'R',
          ),
				),
			),
			'settings' => array (
                            'mapheight' => 400,
                            'zoom' => 10,
                            'minzoom' => 1,
                            'maxzoom' => 18,
			),
		);
		
		
    /* @var $entity \Drupal\openlayers\Entity\OpenLayersMap */
    $tmp = parent::buildForm($form, $form_state);
    
    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Create Map'),
    );
    
    $form['base'] = array(
      '#type' => 'details',
      '#title' => t('Base'),
      '#group' => 'advanced',
    );
    
    $form['base']['map_name'] = $tmp['map_name'];
    $form['base']['map_height'] = $tmp['map_height'];
    $form['base']['zoom'] = $tmp['zoom'];
    $form['base']['minzoom'] = $tmp['minzoom'];
    $form['base']['maxzoom'] = $tmp['maxzoom'];
    $form['base']['center'] = $tmp['center'];
    $form['base']['max_extent'] = $tmp['max_extent'];
    $mapid = Html::getUniqueId('openlayers_map');
    $form['base']['map'] = openlayers_render_map($mapid, $map, null, $map['settings']['mapheight'].'px', null);
		
    $form['layer'] = array(
      '#type' => 'details',
      '#title' => t('Layers'),
      '#group' => 'advanced',
    );
    $form['layer']['layer_ref_base'] = $tmp['layer_ref_base'];
    $form['layer']['layer_ref_overlay'] = $tmp['layer_ref_overlay'];
    
    $form['control'] = array(
      '#type' => 'details',
      '#title' => t('Controls'),
      '#group' => 'advanced',
    );
    $form['control']['control_ref'] = $tmp['control_ref'];
    
    $form['actions'] = $tmp['actions'];
   
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.openlayers.map.collection');
    $entity = $this->getEntity();
    $entity->save();
  }
}
