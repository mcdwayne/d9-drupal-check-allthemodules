<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\Form\WisskiFieldDeleteForm.
 */
 
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that handles the removal of Wisski Path entities
 */
class WisskiFieldDeleteForm extends EntityConfirmFormBase {
  
  private $pb_id;
  private $field_id;
  private $field_type;                      
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    
    $this->pb_id = \Drupal::routeMatch()->getParameter('wisski_pathbuilder');
    $this->field_id = \Drupal::routeMatch()->getParameter('wisski_field_id');
    $this->field_type = \Drupal::routeMatch()->getParameter('wisski_field_type');

#    drupal_set_message("id: " . serialize($this->pb_id));
#    drupal_set_message("fid: " . serialize($this->field_id));
#    drupal_set_message("ft: " . serialize($this->field_type));

    return $this->t('Do you want to delete the field @id associated with this path?',array('@id' => $this->field_id));
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    #drupal_set_message(htmlentities(new Url('entity.wisski_pathbuilder.overview')));
    #return new Url('entity.wisski_pathbuilder.overview');
    #$pb_entities = entity_load_multiple('wisski_pathbuilder');
    # $pb = 'pb';
    if (isset($this->pb_id)) {
      $url = \Drupal\Core\Url::fromRoute('entity.wisski_pathbuilder.edit_form',array('wisski_pathbuilder'=>$this->pb_id));
    } else {
      $url = \Drupal\Core\Url::fromRoute('entity.wisski_pathbuilder.collection');
    }
    return $url;                       
  }
  
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if($this->field_type == "field" || $this->field_type == "both") {
 #     drupal_set_message("it is a field!");
      $field_storages = \Drupal::entityManager()->getStorage('field_storage_config')->loadByProperties(
        array(
          'field_name' => $this->field_id,
          //'entity_type' => $mode,
        )
      );
 #     drupal_set_message("fs: " . serialize($field_storages));
      if (!empty($field_storages)) {
        foreach($field_storages as $field_storage) {
          $field_storage->delete();
        }
      }
      drupal_set_message($this->t('The field with id @id has been deleted.',array('@id' => $this->field_id)));
    }
    
    if($this->field_type == "bundle" || $this->field_type == "both") {
      // bundle mode.
      $bundle_storages = \Drupal::entityManager()->getStorage('wisski_bundle')->loadByProperties(array('id' => $this->field_id));
#      drupal_set_message("bs: " . serialize($bundle_storages));

      if (!empty($bundle_storages)) {
        foreach($bundle_storages as $bundle_storage) {
          $bundle_storage->delete();
        }
      }
      drupal_set_message($this->t('The Bundle with id @id has been deleted.',array('@id' => $this->field_id)));
    }    
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}