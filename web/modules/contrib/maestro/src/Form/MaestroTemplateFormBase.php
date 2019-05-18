<?php

/**
 * @file
 * Contains Drupal\maestro\Form\MaestroTemplateFormBase.
 */

namespace Drupal\maestro\Form;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\views\Views;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class MaestroTemplateFormBase.
 *
 *
 * @package Drupal\maestro\Form
 *
 * @ingroup maestro
 */
class MaestroTemplateFormBase extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *
   * @return array
   *   An associative array containing the Template add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need form the base class.
    $form = parent::buildForm($form, $form_state);

    $isModal = $this->getRequest()->get('is_modal');
    $Template = $this->entity;
    
    //if we're modal, we remove the delete action.
    if($isModal == 'modal') unset($form['actions']['delete']);

    // Build the form.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $Template->label(),
      '#required' => TRUE,
    );
    
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $Template->id(),
      '#machine_name' => array(
        'exists' =>  [get_class($this), 'exists'],
      ),
      //'#disabled' => !$Template->isNew(),  //I believe we should keep the machine name editable
    );

    //The notion of App Groups will be carried across to D8 Maestro, however we
    //will simply hide for the time being as they were used sparingly in the Drupal 7 version
    $form['app_group'] = array(
      '#type' => 'hidden',
      '#title' => $this->t('App Group'),
      '#maxlength' => 255,
      '#default_value' => isset($Template->app_group) ? $Template->app_group : 0,
      '#required' => TRUE,
    );

    //Canvas height is a default value of 900 pixels.
    $form['canvas_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Canvas Height in pixels'),
      '#maxlength' => 255,
      '#default_value' => isset($Template->canvas_height) ? $Template->canvas_height : 900 ,
      '#required' => TRUE,
    );
    
    //default is 800 px wide
    $form['canvas_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Canvas Width in pixels'),
      '#maxlength' => 255,
      '#default_value' => isset($Template->canvas_width) ? $Template->canvas_width : 800 ,
      '#required' => TRUE,
    );

    //default is to not show details
    $form['show_details'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show details of this template\'s process in Task Console details?'),
      '#description' => $this->t('When checked, the task console will enable the showing of details of the process/task.'),
      '#default_value' => isset($Template->show_details) ? $Template->show_details : 0 ,
    );
    
    $form['show_details_area'] = array (
      '#type' => 'fieldset',
      '#title' => $this->t('Details Configuration'),
      '#states' => array(
        'visible' => array(
          ':input[name="show_details"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $all_views = \Drupal\views\Views::getAllViews();
    $options = ['' => $this->t('Select View')];
    foreach($all_views as $machine_name => $view) {
      $options[$machine_name] = $view->label();
    }
    $form['show_details_area']['add_view'] = array(
      '#type' => 'select',
      '#title' => $this->t('Add a view to the details panel.'),
      '#description' => $this->t('You can add a view to be displayed in the details panel in the task console. QueueID and ProcessID are passed to the view as arguments.'),
      '#default_value' => '',
      '#options' => $options,
      '#required' => FALSE,
      '#states' => array(
        'visible' => array(
          ':input[name="show_details"]' => array('checked' => TRUE),
        ),
      ),
      '#ajax' => array(
        'callback' => '::fetchViewsDisplays',
        'wrapper' => 'dropdown-views-bundles-replace',
      ),
    );
    
    //need to do an auto-lookup to get the view's display only cone the add_view has changed 
    $form['show_details_area']['add_view_display'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select the view display.'),
      '#description' => $this->t('The display of the view you wish to add.'),
      '#default_value' => '',
      '#options' => [],
      '#required' => FALSE,
      '#states' => array(
        'visible' => array(
          ':input[name="show_details"]' => array('checked' => TRUE),
          ':input[name="add_view"]' => array('!value' => ''),
        ),
      ),
      '#validated' => TRUE,
      '#prefix' => '<div id="dropdown-views-bundles-replace">',
      '#suffix' => '</div>',
    );
    
    $form['show_details_area']['views'] = array(
      '#type' => 'details',
      '#title' => $this->t('Views attached to details output'),
      '#prefix' => '<div id="views-replace">',
      '#suffix' => '</div>',
      '#open' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="show_details"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    //generate the list of views attached with the option to delete only.
    $views_array = [];
    if(isset($this->entity->views_attached)) {
      foreach($this->entity->views_attached as $key => $view_information) {
        $display = explode(';', $view_information['view_display']);
        $views_array[$view_information['view_weight']] = [
          'machine_name' => $view_information['view_machine_name'],
          'label' => Views::getView($view_information['view_machine_name'])->storage->label(),
          'view_display' => isset($display[1]) ? $display[1] : '',
          ];
      }
    }
    //now have a sorted by weight list of views.  Generate the table
    $form['show_details_area']['views']['views_attached_to_template'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Name'), $this->t('Machine name'), $this->t('Display'), $this->t('Weight'), $this->t('Remove')),
      '#empty' => $this->t('There are no attached views'),
      '#tableselect' => FALSE,
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'views-attached-to-template-order-weight',
        ),
      ),
    );
    
    foreach($views_array as $weight => $view) {
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['#attributes']['class'][] = 'draggable';
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['#weight'] = $weight;
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['name'] = array(
        '#plain_text' => $view['label'],
      );
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['machine_name'] = array(
        '#plain_text' => $view['machine_name'],
      );
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['display'] = array(
        '#plain_text' => $view['view_display'],
      );
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Order'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => array('class' => array('views-attached-to-template-order-weight')),
      );
      
      $form['show_details_area']['views']['views_attached_to_template'][$weight]['delete'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Delete'),
        '#title_display' => 'none',
        '#required' => FALSE,
      );
      
    }
    
    //default is 0
    $form['default_workflow_timeline_stage_count'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The default number of stages you wish to show this workflow having'),
      '#description' => $this->t('Default 0 will show no stages in the task console. Process variable for worflow_timeline_stage_count will
          be set with the value configured'),
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => isset($Template->default_workflow_timeline_stage_count) ? $Template->default_workflow_timeline_stage_count : 0 ,
      '#required' => TRUE,
    );
   
    //variable definitions:
    //Complex type of machine name and variable value
    $form['variables'] = array(
      '#type' => 'details',
      '#title' => $this->t('Variables'),
      '#group' => 'templatevariables',
      '#tree' => TRUE,
      '#prefix' => '<div id="multi-replace">',
      '#suffix' => '</div>'
    );

    $key = 0;
    $mandatory_variables = [  //see /src/Form/MaestroTemplateAddForm.php
      'initiator',
      'workflow_timeline_stage_count',
      'workflow_current_stage',
      'workflow_current_stage_message',
    ];
    
    if(isset($this->entity->variables)) {
      foreach($this->entity->variables as $key => $variable) {
        $disabled = FALSE;
        $attributes = NULL;
        $class = 'maestro-existing-variable';
        if( array_search($key, $mandatory_variables) !== FALSE ) {
          //do not allow the mandatory variables to be deleted
          $disabled = TRUE;
          $class = 'maestro-existing-variable-no-delete';
          $attributes = array(
            'title' => t("You cannot delete this variable.  This variable is required by Maestro."),
          );
        }
        
        $form['variables'][$key]['delete'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Delete'),
          '#title_display' => 'before',
          '#required' => FALSE,
          '#prefix' => '<div class="clearfix ' . $class . '">',
          '#disabled' => $disabled,
          '#attributes' => $attributes,
        );
        
        $form['variables'][$key]['variable_id'] = array(
          '#type' => 'machine_name',
          '#title' => $this->t('Variable name'),
          '#default_value' => isset($this->entity->variables[$key]['variable_id']) ? $this->entity->variables[$key]['variable_id'] : '',
          '#machine_name' => array(
              'exists' =>  [get_class($this), 'exists'],
          ),
          '#required' => FALSE,
          '#disabled' => $disabled,
        );
        
        $form['variables'][$key]['variable_value'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Value'),
          '#maxlength' => 255,
          '#default_value' => isset($this->entity->variables[$key]['variable_value']) ? $this->entity->variables[$key]['variable_value'] : '',
          '#required' => FALSE,
          '#suffix' => '</div>',
        );
      }
    }
    
    //form elements for a new variable
    $form['variables']['new_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => 'Create New Variable'
    );
    
    $form['variables']['new_fieldset']['new']['variable_id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Variable name'),
      '#default_value' => '',
      '#machine_name' => array(
          'exists' =>  [get_class($this), 'exists'],
      ),
      '#required' => FALSE,
    );
    
    $form['variables']['new_fieldset']['new']['variable_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#required' => FALSE,
    );
    
    $form['#attached']['library'][] = 'maestro/maestro-engine-css';
    return $form;
  }

  public function fetchViewsDisplays(array $form, FormStateInterface $form_state) {
    $options = [];
    if($form_state->getValue('add_view') != '') {
      
      $view = Views::getView($form_state->getValue('add_view'));
      if($view) {
        foreach($view->storage->get('display') as $display_machine_name => $arr) {
          $options [$display_machine_name . ';' . $arr['display_title']] = $arr['display_title'];
        }
      }
      else {
        $options[''] = $this->t('Please reselect View');
      }
      $form['show_details_area']['add_view_display']['#options'] = $options;
      
    }
    else {
      $form['show_details_area']['add_view_display'] = array(
        '#display' => FALSE,
        '#prefix' => '<div id="dropdown-views-bundles-replace">',
        '#suffix' => '</div>',
      );
    }
    
    return $form['show_details_area']['add_view_display'];
  }
  
  
  static public function exists($submitted_value, array $element, FormStateInterface $form_state) {
    $templates = MaestroEngine::getTemplates();
    if(array_key_exists($submitted_value, $templates)) {
      return TRUE;
    }
    return FALSE;
  }
  
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    
    if($form_state->getValue('add_view') && $form_state->getValue('add_view') != '') {
      $view = \Drupal\views\Views::getView($form_state->getValue('add_view'));
      if(!$view) {
        $form_state->setErrorByName('add_view', $this->t('An invalid view was entered'));
      }
    }
     
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   */
  public function save(array $form, FormStateInterface $form_state) {
    $isModal = $this->getRequest()->get('is_modal');
    //remove the 'new' variable values here so that the save, well, saves them.
    //form_state->values['variables'] holds the saved variables
    //we need to ensure that the variables at this stage, since the parent validation has happened
    //is properly formatted to remove the 'new' key and add that to the numerical keys
    
    $variables = $form_state->getValue('variables');
    $new = $variables['new_fieldset']['new'];
    unset($variables['new_fieldset']);
    if(isset($new['variable_id']) && !empty($new['variable_id'])) {
      //we have a new variable here.
      $variables[$new['variable_id']] = $new;
    }
    
    //now to handle deletion
    foreach($variables as $key => $variable) {
      if(array_key_exists('delete', $variable) && isset($variable['delete'])) {
        if($variable['delete'] == 1) unset($variables[$key]);
        else unset($variables[$key]['delete']);
      }
    }
    
    $this->entity->variables = $variables;
    
    //now handle attached views and handle the ordering properly
    $views_ordering_from_form = $form_state->getValue('views_attached_to_template');
    $existing_views = [];
    if(isset($this->entity->views_attached)) {
      foreach($this->entity->views_attached as $machine_name => $arr) {
        $existing_views[$arr['view_weight']] = $machine_name;
      }
    }
    $views_ordering = [];
    if(!empty($views_ordering_from_form)) {
      foreach($views_ordering_from_form as $key => $arr) {
        if(is_numeric($key)) {
          $views_ordering[$arr['weight']] = $existing_views[$key];
          if($arr['delete']) unset($views_ordering[$arr['weight']]);
        }
      }
    }
    ksort($views_ordering);
    $views_attached = [];
    $lowest_weight = 0;
    foreach($views_ordering as $weight => $machine_name) {
      if($weight <= $lowest_weight) $lowest_weight = $weight -1;
      $views_attached[$machine_name] = ['view_machine_name' => $machine_name, 'view_weight' => $weight, 'view_display' => $this->entity->views_attached[$machine_name]['view_display']];
    }
    //now if there's a new view, attach it
    if($form_state->getValue('add_view')) {
      $views_attached[$form_state->getValue('add_view')] = ['view_machine_name' => $form_state->getValue('add_view'), 'view_weight' => $lowest_weight, 'view_display' =>  $form_state->getValue('add_view_display')];
    }

    $this->entity->views_attached = $views_attached;
    $status = $this->entity->save();
    
    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      drupal_set_message($this->t('Template %label has been updated.', array('%label' => $this->entity->label())));
      \Drupal::logger('maestro')->notice('Template %label has been updated.', array('%label' => $this->entity->label()));
      if($isModal == 'modal') {
        $response = new AjaxResponse();
        $response->addCommand(new CloseModalDialogCommand());
       return $response;
      }
    }
    else {
      // If we created a new entity...
      drupal_set_message($this->t('Template %label has been added.', array('%label' => $this->entity->label())));
      \Drupal::logger('maestro')->notice('Template %label has been added.', array('%label' => $this->entity->label()));
      $form_state->setRedirect('entity.maestro_template.list');
    }

    
  }

}
