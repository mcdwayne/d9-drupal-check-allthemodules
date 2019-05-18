<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\LayerForm.
 */

namespace Drupal\openlayers\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\openlayers\Controller\ExternalLayerTree;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class LayerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  private $sourcelayerrelation = [];
  
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    /*
     * kind of source => kind of layer
     * OSM, XYZ => tile
     * ImageWMS => image 
     * vector => view, node
     */
    $this->sourcelayerrelation['tile'][0] = 'osm';
    $this->sourcelayerrelation['tile'][1] = 'XYZ';
    $this->sourcelayerrelation['image'][0] = 'imagewms';
    $this->sourcelayerrelation['node'][0] = 'vector';
    $this->sourcelayerrelation['view'][0] = 'vector';
    
    $form['wrapper'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'data-wrapper'),
    );
    /* @var $entity \Drupal\openlayers\Entity\OpenLayersLayer */
    $form['wrapper'][] = parent::buildForm($form, $form_state);
	
    $form['wrapper'][0]['layer_type']['widget']['#ajax'] = [
      'callback' => '::rebuildLayerForm',
      'event' => 'change',
      'wrapper' => 'data-wrapper',
      'progress' => [
        'type' => 'throbber',
        'message' => t('...'),
      ],
    ];
    
    /*
     * Check if Source is defined.
     */
    if (isset($form_state->getValue('layer_source_ref')[0]['target_id'])) {
      $tmp_sourceid = $form_state->getValue('layer_source_ref')[0]['target_id'];
    } else {
      $tmp_sourceid = $form['wrapper'][0]['layer_source_ref']['widget']['#default_value'];
    }
    
    if(sizeof($form['wrapper'][0]['layer_source_ref']['widget']['#default_value']) > 0) {
      $form['wrapper'][0]['layer_type']['#disabled'] = TRUE;
    }
    
    /*
     * Logic could be shorter.
     */
    if( sizeof($form_state->getValue('layer_type')) < 1 && sizeof($form['wrapper'][0]['layer_type']['widget']['#default_value']) < 1) {
      $layerType = 'none';
    }
    else {
      if(sizeof($form_state->getValue('layer_type')) < 1) {
        $layerType = $form['wrapper'][0]['layer_type']['widget']['#default_value'][0];
      }
      else {
        $layerType = $form_state->getValue('layer_type')[0]['value'];
      }
    }
 
    switch ($layerType) {
      case 'none':
        $form['wrapper'][0]['layer_source_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_name']['#access'] = FALSE;
        $form['wrapper'][0]['layer_view_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_node_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_machine']['#access'] = FALSE;
        break;
      case 'tile':
        $form['wrapper'][0]['layer_source_ref']['widget']['#options'] = $this->buildSourcesforLayerType('tile');
        $form['wrapper'][0]['layer_source_ref']['#access'] = TRUE;
        $form['wrapper'][0]['layer_name']['#access'] = TRUE;
        $form['wrapper'][0]['layer_view_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_node_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_machine']['#access'] = FALSE;
        break;
      case 'image':
        $form['wrapper'][0]['layer_source_ref']['widget']['#options'] = $this->buildSourcesforLayerType('image');
        $form['wrapper'][0]['layer_source_ref']['widget']['#ajax']= [
          'callback' => '::rebuildLayerForm',
          'event' => 'change',
          'wrapper' => 'data-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => t('...'),
          ],
        ];
        $form['wrapper'][0]['layer_machine']['widget']['#options'] = $this->buildLayerOptions($tmp_sourceid);
        $form['wrapper'][0]['layer_machine']['widget']['#default_value'] = TRUE;
        $form['wrapper'][0]['layer_source_ref']['#access'] = TRUE;
        $form['wrapper'][0]['layer_name']['#access'] = TRUE;
        $form['wrapper'][0]['layer_view_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_node_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_machine']['#access'] = TRUE;
        //$form['wrapper'][0]['layer_machine']['widget']['#attributes'] =  array('checked' => 'checked');
        break;
      case 'node':
        $form['wrapper'][0]['layer_source_ref']['widget']['#options'] = $this->buildSourcesforLayerType('node');
        $form['wrapper'][0]['layer_source_ref']['#access'] = TRUE;
        $form['wrapper'][0]['layer_name']['#access'] = TRUE;
        $form['wrapper'][0]['layer_view_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_node_ref']['#access'] = TRUE;
        $form['wrapper'][0]['layer_machine']['#access'] = FALSE;
        break;
      case 'view':
        $form['wrapper'][0]['layer_source_ref']['widget']['#options'] = $this->buildSourcesforLayerType('view');
        $form['wrapper'][0]['layer_source_ref']['#access'] = TRUE;
        $form['wrapper'][0]['layer_name']['#access'] = TRUE;
        $form['wrapper'][0]['layer_view_ref']['widget']['#options'] = $this->buildViewsforLayerType();
        $form['wrapper'][0]['layer_view_ref']['#access'] = TRUE;
        $form['wrapper'][0]['layer_node_ref']['#access'] = FALSE;
        $form['wrapper'][0]['layer_machine']['#access'] = FALSE;
        break;
    }
    return $form;
  }

  public function rebuildLayerForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(true); 
    return $form['wrapper'];
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Redirect to term list after save.
    $form_state->setRedirect('entity.openlayers.layer.collection');
    $entity = $this->getEntity();
    $entity->save();
  }
  
  /*
   * build the form depend on the choosen layer type
   */
  private function buildSourcesforLayerType($type) {
    $options =[];
    $ids = [];
    foreach ($this->sourcelayerrelation[$type] as $_type) {
      $_ids = \Drupal::entityQuery('openlayers_source')
        ->condition('source_type',$_type)
        ->execute();
      if (sizeof($_ids) > 0) {
        foreach ($_ids as $id) {
          array_push($ids, $id);
        }
      }
    }
    
    $sources = \Drupal::entityManager()->getStorage('openlayers_source')->loadMultiple($ids);
    
    foreach ($sources as $source) { 
      $options[$source->id()] = $source->label();
    }
    
    return $options;
  }
  
  //for Views
  private function buildViewsforLayerType() {
    $options = [];
    
    $views = \Drupal::entityQuery('view')->execute();
    
    foreach ($views as $key => $view) {
      $_view = entity_load('view', $view);
      $fields = $_view->get('display')['default']['display_options']['fields'];
      
      if (sizeof($fields) >0) {
        foreach($fields as $field_name => $field) {
          if($field['type'] === 'geofield_default' || $field['type'] === 'geofield' ) {
            $options[$_view->id()] = $_view->label();
            break;
          }
        }
      };
    }     
    return $options;    
  }
  
  private function buildLayerOptions($source_id) {
    dd("husten");
    if (sizeof($source_id) > 0 ) {
      $layerTree = new ExternalLayerTree($source_id);
      return $layerTree->getOptions();
    } else {
      return array();
    }
      
    

  }
}
