<?php
/**
 * @file
 * Contains \Drupal\wisski_pathbuilder\Form\WisskiPathDuplicateForm.
 */
 
namespace Drupal\wisski_pathbuilder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that handles the removal of Wisski Path entities
 */
class WisskiPathDuplicateForm extends EntityConfirmFormBase {
  
  private $pb_id;
                                
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    
    $path = $this->entity;
    $this->pb_id = \Drupal::routeMatch()->getParameter('wisski_pathbuilder');
#    dpm($path);
    return $this->t('Are you sure you want to duplicate path "@id"?', array('@id' => $path->id()));
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
    return $this->t('Duplicate');
  }
  
  private function recursiveAddPathToPb($path, $pb, $parent) {
    
#    dpm($path, 'duplicating ');
#    dpm($pb->getPbPaths(), "pbp");
#    dpm($pb->getPathTree(), "pt");
    
#    return;    
    // simply add it.
    $newpath = $path->createDuplicate();
    $newpath->set('label', $path->label());
    $newpath->set('id', $path->id() . '_' . uniqid());
    $newpath->save();
    
    $pb->addPathToPathTree($newpath->id(), $parent, $newpath->isGroup());

    // overwrite the pbp-values because these would not be duplicated otherwise.    
    $pbp = $pb->getPbPaths();
    $pbp[$newpath->id()] = $pbp[$path->id()];
    $pbp[$newpath->id()]['id'] = $newpath->id();
    $pb->setPbPaths($pbp);
    
#    dpm($pbp, "pbp");
    
    if($newpath->isGroup()) {
      // iterate everything of the original path - the new one does not have anything below.
      $all_paths_and_groups = $pb->getPathsAndGroupsForGroupId($path->id());
#      dpm($all_paths_and_groups, "apag");     
      foreach($all_paths_and_groups as $apug) {
        $this->recursiveAddPathToPb($apug, $pb, $newpath->id());
      } 
    }  
#    dpm($pb->getPathTree(), "pt");
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $path = $this->entity;
    $path_id = $path->getID();
    // Delete and set message
#    drupal_set_message(serialize($this->pb_id));
#    $path->delete();
    if (isset($this->pb_id) && $pb = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::load($this->pb_id)) {
      if ($pb->hasPbPath($path_id)) {

        $pbpath = $pb->getPbPath($path_id);

        $this->recursiveAddPathToPb($path, $pb, $pbpath['parent']);
        
        $pb->save();
      }
    }
    drupal_set_message($this->t('The path @id has been duplicated.',array('@id' => $path_id)));
#    $form_state->setRedirectUrl($this->getCancelUrl());

#    drupal_set_message("pbp: " . serialize($pbpath));

#    if(!empty($pbpath)) {
#      if(!empty($pbpath['bundle']) && !empty($pbpath['field']))
#        $form_state->setRedirect('entity.wisski_path.delete_core',array('wisski_pathbuilder'=>$this->pb_id, 'wisski_field_id' => $pbpath['bundle'], 'wisski_field_type' => 'both'));
#      if(empty($pbpath['bundle']) && !empty($pbpath['field']))
#        $form_state->setRedirect('entity.wisski_path.delete_core',array('wisski_pathbuilder'=>$this->pb_id, 'wisski_field_id' => $pbpath['field'], 'wisski_field_type' => 'field'));
#      if(!empty($pbpath['bundle']) && empty($pbpath['field']))
#        $form_state->setRedirect('entity.wisski_path.delete_core',array('wisski_pathbuilder'=>$this->pb_id, 'wisski_field_id' => $pbpath['bundle'], 'wisski_field_type' => 'bundle'));
#    } else {
   $form_state->setRedirectUrl($this->getCancelUrl());
#    }
  }

}