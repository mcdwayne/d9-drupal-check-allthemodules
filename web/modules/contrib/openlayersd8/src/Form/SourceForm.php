<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\SourceForm.
 */

namespace Drupal\openlayers\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class SourceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
    
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['wrapper'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'data-wrapper'),
    );
      
    /* @var $entity \Drupal\openlayers\Entity\OpenLayersSource */
    
    $form['wrapper'][] = parent::buildForm($form, $form_state);
    
    $form['wrapper'][0]['source_type']['widget']['#ajax'] =[
      'callback' => '::rebuildSourceForm',
      'event' => 'change',
      'wrapper' => 'data-wrapper',
      'progress' => [
        'type' => 'throbber',
        'message' => t('...'),
      ],
    ];
    
    /*
     * Logic could be shorter.
     */
    if( sizeof($form_state->getValue('source_type')) < 1 && sizeof($form['wrapper'][0]['source_type']['widget']['#default_value']) < 1) {
      $sourceType = 'none';
    }
    else {
      if(sizeof($form_state->getValue('source_type')) < 1) {
        $sourceType = $form['wrapper'][0]['source_type']['widget']['#default_value'][0];
      }
      else {
        $sourceType = $form_state->getValue('source_type')[0]['value'];
      }
    }
    
    switch ($sourceType) {
      case 'none':
        $form['wrapper'][0]['source_name']['#access'] = FALSE;
        $form['wrapper'][0]['server_type']['#access'] = FALSE;
        $form['wrapper'][0]['source_url']['#access'] = FALSE;
        break;
      case 'osm':
        $form['wrapper'][0]['source_name']['#access'] = TRUE;
        $form['wrapper'][0]['server_type']['#access'] = FALSE;
        $form['wrapper'][0]['source_url']['#access'] = FALSE;
        break;
      case 'xyz':
        $form['wrapper'][0]['source_name']['#access'] = TRUE;
        $form['wrapper'][0]['server_type']['#access'] = FALSE;
        $form['wrapper'][0]['source_url']['#access'] = TRUE;
        break;
      case 'imagewms':
        $form['wrapper'][0]['source_name']['#access'] = TRUE;
        $form['wrapper'][0]['server_type']['#access'] = TRUE;
        $form['wrapper'][0]['source_url']['#access'] = TRUE;
        break;
      case 'vector':
        $form['wrapper'][0]['source_name']['#access'] = TRUE;
        $form['wrapper'][0]['server_type']['#access'] = TRUE;
        $form['wrapper'][0]['source_url']['#access'] = TRUE;
        break;
      case 'drupalintern':
        $form['wrapper'][0]['source_name']['#access'] = TRUE;
        $form['wrapper'][0]['server_type']['#access'] = FALSE;
        $form['wrapper'][0]['source_url']['#access'] = FALSE;
        break;
    }
       
    return $form;
  }
  
public function rebuildSourceForm(array &$form, FormStateInterface $form_state) {
  $form_state->setRebuild(true); 
  return $form['wrapper'];
}
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Redirect to term list after save.
    $form_state->setRedirect('entity.openlayers.source.collection');
    $entity = $this->getEntity();
    $entity->save();
  }
}
