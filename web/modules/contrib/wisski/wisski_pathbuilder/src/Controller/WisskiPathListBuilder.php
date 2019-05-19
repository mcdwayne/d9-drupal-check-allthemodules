<?php
/**
 * @file
 *
 * Contains drupal\wisski_pathbuilder\WisskiPathbuilderListBuilder
 */

namespace Drupal\wisski_pathbuilder\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\String as utilitystring;

/**
 * Defines a class to build a listing of pathbuilder path entities
 */
class WisskiPathListBuilder extends DraggableListBuilder {
  /**
   * {@inheritdoc}
   */
  public function getFormId(){
    return 'wisski_pathbuilder_wisski_path_form';
  }      

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    #$header['id'] = $this->t('id');
    #$header['label'] = $this->t('name');
    $header['label'] = t('Name');        
    return $header + parent::buildHeader();
  }
    
 /**
  * {@inheritdoc}
  */
  public function buildRow(EntityInterface $entity) {         
    // id
   # $row['id'] = $entity->id();
    $row['label'] = $this->getLabel($entity);
    #$this->getLabel($entity);
    #$row['label'] = array(
    #       'data' => $this->getLabel($entity),
    #             'class' => array('menu-label'),
    #                 );                     
               
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message(t('The WissKI Path settings have been updated.'));
  }
        
}                                                      