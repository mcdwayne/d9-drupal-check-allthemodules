<?php

namespace Drupal\webform_openlayers\Element;

use Drupal\Component\Utility\Html;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'webform_openlayers'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("webform_openlayers_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_openlayers\Element\WebformExampleComposite
 */
class WebformOpenLayers_Composite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_openlayers'];	
  }
  
  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    //dsm(GeoPHPWrapper::version());
    //dsm(\geoPHP::version());
    // Generate an unique ID that can be used by #states.
    
    $mapid = Html::getUniqueId('openlayers_map');
    $map = openlayers_map_get_info($element['#map']);
    
    $elements = [];		
    $elements['map'] = openlayers_render_map($mapid, $map, null, $map['settings']['mapheight'].'px', $element['#geometry']);
    
    $type = 'textarea';
    if(isset($element['#showBox'])){
      if($element['#showBox'] === 1) {
        $type = 'hidden';
      }
    };
    
    $elements['value'] = [
      '#type' => $type,
      '#title' => t('Geom as WKT'),
      '#required' => $element['#required'],
      //'#default_value' => $items[$delta]->value ?: NULL,
      '#attributes' => ['id' => $mapid.'-wktbox'],
    //  '#element_validate' => [[get_called_class(), 'geomValidate']],
    ];
		
    return $elements;
  }
	
  public static function geomValidate(&$element, FormStateInterface &$form_state) {
    
    
  }


  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    //dd('validationCB');
    dsm(\geoPHP::version());
    $geom_tobewithin = \geoPHP::load($element['#validation_iswithinGeom'],'wkt');
    
    
    
    
    $form_state->setError($element, t('Error'));
    //dsm($element);
  }
}
