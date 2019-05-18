<?php 

/**
 * @file
 * Definition of Drupal\maestro\Plugin\views\field\MaestroEngineActiveAssignments
 */
 
namespace Drupal\maestro\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\maestro\Engine\MaestroEngine;


/**
 * Field handler to generate a list of assigned users/roles etc.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("maestro_active_assignments")
 */
class MaestroEngineActiveAssignments extends FieldPluginBase {

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
     
    $options['show_how_assigned'] = ['default' => '0'];
    $options['separator_text'] = ['default' => ','];
    return $options;
  }
  
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['show_how_assigned'] = array(
      '#title' => $this->t('When checked, this will add a suffix of :Fixed or :Variable to the assigned entity name.'),
      '#type' => 'checkbox',
      '#default_value' => isset($this->options['show_how_assigned']) ? $this->options['show_how_assigned'] : 0,
    );
    
    $form['separator_text'] = array(
      '#title' => $this->t('Text used for separating multiple values. HTML allowed.'),
      '#type' => 'textfield',
      '#default_value' => isset($this->options['separator_text']) ? $this->options['separator_text'] : ',',
    );
  
    parent::buildOptionsForm($form, $form_state);
  }
  
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $item = $values->_entity;
    $output = '';
    //lets get the assignments based on this queue ID
    $assignees = MaestroEngine::getAssignedNamesOfQueueItem($item->id->getString(), TRUE);
    if(count($assignees) == 0) {
      return $this->t('No assignees'); 
    }
    else {
      foreach($assignees as $arr) {
        if($output != '') $output .= $this->options['separator_text'];
        $output .= $arr['assign_id'];
        if($this->options['show_how_assigned']) $output .= $arr['by_variable'];
      }
      return [
        '#markup' => $output,
      ];
    }
    
  }
}