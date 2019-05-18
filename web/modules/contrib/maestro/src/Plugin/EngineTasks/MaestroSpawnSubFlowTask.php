<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Url;
use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

/**
 * Maestro Spawn Sub Flow Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroSpawnSubFlow",
 *   task_description = @Translation("The Maestro Engine's Spawn Sub Flow Task."),
 * )
 */
class MaestroSpawnSubFlowTask extends PluginBase implements MaestroEngineTaskInterface {

  use MaestroTaskTrait;
 
  function __construct($configuration = NULL) {
    if(is_array($configuration)) {
      $this->processID = $configuration[0];
      $this->queueID = $configuration[1];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function isInteractive() {
    return FALSE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return t('Spawn Sub Flow');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Spawn Sub Flow.');
  }

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroSpawnSubFlow';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
   return '#707070';
  }
  
  
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
   
  }
  
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the Sub Flow task will create a new process and push all selected parent variables to the newly spawned
   * sub process.  The variables pushed to the sub process will be prefixed with "maestro_parent_" and will also include a new 
   * variable named "parent_process_id" which will store the process ID of the parent.
   * {@inheritdoc}
   */
  public function execute() {
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);
    
    $spawnTemplate = $task['data']['maestro_template'];
    $variables = [];
    if(isset($task['data']['variables'])) {
      $variables = $task['data']['variables'];
    }
    //Let's first start by adding in the new process ID
    $maestro = new MaestroEngine();
    $newProcessID = $maestro->newProcess($spawnTemplate);
    if($newProcessID !== FALSE) {
      //first, create the parent process ID variable
      $values = array (
        'process_id' => $newProcessID,
        'variable_name' => 'maestro_parent_process_id',
        'variable_value' => $this->processID,
      );
      $new_var = \Drupal::entityTypeManager()->getStorage('maestro_process_variables')->create($values);
      $new_var->save();
      if(!$new_var->id()) {
        //throw a maestro exception
        //completion should technically end here for this initiation
        throw new \Drupal\maestro\Engine\Exception\MaestroSaveEntityException('maestro_process_variable', $values['variable_name'] . ' failed saving during new process creation.');
      }
      
      foreach($variables as $machine_name => $checked_value) {
        if($machine_name != '') {
          //we now populate the new process with variables
          $parent_value = MaestroEngine::getProcessVariable($machine_name, $this->processID);
          $values = array (
            'process_id' => $newProcessID,
            'variable_name' => 'maestro_parent_' . $machine_name,
            'variable_value' => $parent_value,
          );
          $new_var = \Drupal::entityTypeManager()->getStorage('maestro_process_variables')->create($values);
          $new_var->save();
          if(!$new_var->id()) {
            //throw a maestro exception
            //completion should technically end here for this initiation
            throw new \Drupal\maestro\Engine\Exception\MaestroSaveEntityException('maestro_process_variable', $values['variable_name'] . ' failed saving during new process creation.');
          }
          $parent_value = '';
        }
      }
      
        
    }
    else {
      \Drupal::logger('maestro')->error('Unable to spawn sub process.  Process spawn returned an error.');
      return FALSE;
    }
    
    
  }
  
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = array(
      '#markup' => t('Spawn Sub Flow Edit'),
    );
    
    $maestro_templates = MaestroEngine::getTemplates();
    $templates = [];
    $templates['none'] = $this->t('Please Select Template');
    foreach($maestro_templates as $machine_name => $template) {
      $templates[$machine_name] = $template->label();
    }
    
    $form['maestro_task_machine_name'] = [
      '#type' => 'hidden',
      '#value' => $task['id'],
    ];
    
    $form['maestro_template_machine_name'] = [
      '#type' => 'hidden',
      '#value' => $templateMachineName,
    ];
    
    $selected_template = '';
    if(isset($task['data']['maestro_template'])) {
      $selected_template = $task['data']['maestro_template'];
    }
    
    
    $form['maestro_template'] = array(
      '#type' => 'select',
      '#options' => $templates,
      '#title' => $this->t('Choose the Maestro Template'),
      '#default_value' => $selected_template,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'subFlowChoiceHandlerCallback'],
        'event' => 'change',
        'wrapper' => 'handler-ajax-refresh-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    );
    
    $template_machine_name = $selected_template;
    $form_state_template = $task['form_state']->getValue('maestro_template');
    if(isset($form_state_template)) {
      $template_machine_name = $form_state_template;
    }
    
    if($template_machine_name != 'none' && $template_machine_name != '') {
      $template = MaestroEngine::getTemplate($template_machine_name);
      $form['maestro_sub_flow_label'] = [
        '#type' => 'link',
        '#title' => $this->t('Chosen Template') . ': ' . $template->label(),
        '#url' => Url::fromRoute('maestro_template_builder', ['templateMachineName' => $template_machine_name]),
        '#attributes' => [
          'class' => ['handler-help-message'],
          'target' => '_new',
          'id' => ['handler-ajax-refresh-wrapper'],
        ],
        
      ];
    }
    else {
      $form['maestro_sub_flow_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Please choose a template.'),
        '#attributes' => [
          'class' => ['handler-help-message'],
          'id' => ['handler-ajax-refresh-wrapper'],
        ],
      ];
  
    }
    
    $form['maestro_sub_flow_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Choose the variables you wish to send from the parent to the child. Variables will be prefixed with "maestro_parent_" when injected into the sub-process.'),
      '#title' => $this->t('Variable Selection'),
    ];
    
    $template = MaestroEngine::getTemplate($templateMachineName);
    
    $form['maestro_sub_flow_settings']['variables'] = [];
    
    //get all variables other than some of the exclusive Maestro variables.
    foreach($template->variables as $var_name => $var_definition) {
      $form['maestro_sub_flow_settings']['variables']['variable_' . $var_name] = array(
        '#type' => 'checkbox',
        '#title' => $var_name,
        '#default_value' => @array_key_exists($var_name, $task['data']['variables'])? TRUE : FALSE,
        '#attributes' => [
          'autocomplete' => 'off',
        ],
      );
    }
     
    $form['#cache'] = ['max-age' => 0];
    $form['#attributes']['autocomplete'] = 'off'; //Hi Firefox, I see you caching.
    return $form;
  }


  /**
   * Implements callback for Ajax event on objective selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Objective selection section of the form.
   */
  public function subFlowChoiceHandlerCallback(array $form, FormStateInterface &$form_state) {
    return $form['maestro_sub_flow_label'];
  }

  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
    $template = $form_state->getValue('maestro_template');
    //Let's validate the handler here to ensure that it actually exists.
    if($template == 'none') {
      $form_state->setErrorByName('maestro_template', $this->t('You must choose a template.'));
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    $task['data']['maestro_template'] = $form_state->getValue('maestro_template');
    //now handle the variables
    unset($task['data']['variables']);
    $all_values = $form_state->getValues();
    foreach($all_values as $key => $var) {
      if(strpos($key, 'variable_') === 0) {
        //starts with 'variable_',so we know this is our variables
        $is_checked = $form_state->getValue($key);
        if($is_checked) {
          $varname = substr($key, 9);  //strip of "variable_"
          $task['data']['variables'][$varname] = 1;  //signal that it's checked.
        }
      }
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    //so we know that we need a few keys in this $task array to even have a batch function run properly.
    //namely the handler
    
    if( (array_key_exists('maestro_template', $task['data']) && $task['data']['maestro_template'] == '')  || !array_key_exists('maestro_template', $task['data'])) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('This task requires a Maestro Template to be chosen.'),
      );
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    return array('edit', 'drawlineto', 'removelines', 'remove');
  }
}
