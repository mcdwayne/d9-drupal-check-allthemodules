<?php 

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineCompletedTimestamp
 */
 
namespace Drupal\maestro\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\maestro\Engine\MaestroEngine;


/**
 * Field handler to generate an edit link to the entity if possible based on user perms
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_entity_identifiers_edit_link")
 */
class MaestroEngineEntityIdentifierEditLink extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // no Query to be done.
  }
  
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['edit_text'] = ['default' => $this->t('Edit')];
  
    return $options;
  }
  
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['edit_text'] = array(
      '#title' => $this->t('Link Text'),
      '#type' => 'textfield',
      '#default_value' => isset($this->options['edit_text']) ? $this->options['edit_text'] : 'Edit',
    );
  
    parent::buildOptionsForm($form, $form_state);
  }
  
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $editUrlToEntity = '';
    $item = $values->_entity;
    //this will ONLY work for maestro entity identifiers
    if ($item->getEntityTypeId() == 'maestro_entity_identifiers') {
      $entity_manager = \Drupal::entityTypeManager();
      $entity = $entity_manager->getStorage($item->entity_type->getString())->load($item->entity_id->getString());
      if(isset($entity)) {
        $queueID = 0;
        if(isset($this->view->args[1]))  $queueID = $this->view->args[1];
        $editUrlToEntity = $entity->access('update') !== FALSE ? $entity->toUrl('edit-form', ['query' => ['maestro' => 1]])->toString() : '';
      }
      else {
        $result = '';
      }
    }
    else {
      return '';
    }
    
    if($this->options['edit_text'] && $editUrlToEntity) {
      return ['#markup' => '<a href="' . $editUrlToEntity . '" class="maestro_who_completed_field">' . $this->options['edit_text'] . '</a>'];
    }
    else {
      return '';
    } 
    
  }
}