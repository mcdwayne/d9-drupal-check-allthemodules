<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

/**
 * Maestro Set Process Variable Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroSetProcessVariable",
 *   task_description = @Translation("The Maestro Engine's Set Process Variable task."),
 * )
 */
class MaestroSetProcessVariableTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('Set Process Variable');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Sets a process variable to the desired value.');
  }

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroSetProcessVariable';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
   return '#daa520';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the set process variable task.  We will read the data in the template for what we should do with the process variable
   * {@inheritdoc}
   */
  public function execute() {
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);
    
    $spv = $task['data']['spv'];
    $variable = $spv['variable'];
    $variableValue = $spv['variable_value'];
    
    switch($spv['method']) {
      case 'addsubtract':
        //simple here.. our value is a floatval.  This can be negative or positive.  Just add it to the current process variable
        $processVar = MaestroEngine::getProcessVariable($variable, $this->processID);
        $processVar = $processVar + $variableValue;
        MaestroEngine::setProcessVariable($variable, $processVar, $this->processID);
        return TRUE;  //completes the task here.
        break;
      
      case 'hardcoded':
        //as easy as just taking the variable's value and setting it to what the template tells us to do
        MaestroEngine::setProcessVariable($variable, $variableValue, $this->processID);
        return TRUE; //completes the task here.
        break;
        
      case 'bycontentfunction':
        $arr = explode(':', $variableValue);  //[0] is our function, the rest are arguments
        $arguments = explode(',', $arr[1]);
        $arguments[] = $this->queueID;
        $arguments[] = $this->processID;
        
        $newValue = call_user_func_array($arr[0], $arguments);
        MaestroEngine::setProcessVariable($variable, $newValue, $this->processID);
        return TRUE; //completes the task here.
        break;
    }
    //we are relying on the base trait's default values to set the execution and completion status
  }
  
  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {} //not interactive.. we do nothing 
  
  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {}  //not interactive.. we do nothing.
  
  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    $spv = $task['data']['spv'];
    
    $form = array(
      '#markup' => $this->t('Editing the Process Variable Task'),
    );
    
    $form['spv'] = array(
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Choose which process variable you wish to set and how'),
      '#collapsed' => FALSE,
    );
    
    $variables = MaestroEngine::getTemplateVariables($templateMachineName);
    $options = array();
    foreach($variables as $variableName => $arr) {
      $options[$variableName] = $variableName;
    }
    
    $form['spv']['variable'] = array(
      '#type' => 'select',
      '#title' => $this->t('Choose the variable'),
      '#required' => TRUE,
      '#default_value' => isset($spv['variable']) ? $spv['variable'] : '',
      '#options' => $options,
    );
    
    
    //TODO: add other options here such as the content field result
    //however, the content field result needs to be rethought on how we're leveraging content
    $form['spv']['method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Method to set variable'),
      '#options' => array(
        'hardcoded' => $this->t('Hardcoded Value'),
        'addsubtract' => $this->t('Add or Subtract a Value'),
        'bycontentfunction' => $this->t('By Function. Pass "function_name:parameter1,parameter2,..." in variable 
                                          as comma separated list. e.g. maestro_spv_content_value_fetch:unique_identifier,field_your_field')
        ),
      '#default_value' => isset($spv['method']) ? $spv['method'] : '',
      '#required' => TRUE,
    );
    
    $form['spv']['variable_value']  = array(
      '#type' => 'textfield',
      '#title' => $this->t('Variable value'),
      '#description' => $this->t('The value you wish to set the variable to based on your METHOD selection above'),
      '#default_value' => isset($spv['variable_value']) ? $spv['variable_value'] : '',
      '#required' => TRUE,
    );
    
    return $form;
  }
  
  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
    $spv = $form_state->getValue('spv'); //these are the set process variable values
    
    switch($spv['method']) {
      case 'addsubtract':
        $addSubValue = $spv['variable_value'];
        $float = floatval($addSubValue);
        if(strcmp($float, $addSubValue) != 0) {
          $form_state->setErrorByName('spv][variable_value', $this->t('The add or subtract mechanism requires numerical values only.'));
        }
        break;
        
      case 'hardcoded':
        //we don't care what they hard code a variable to quite frankly.  But we at least trap the case here
        //in the event we need to do some preprocessing on it in the future.  Hook?  
        break;
     
      case 'bycontentfunction':
        //in here we use the variable value and parse out the function, content type and field we wish to fetch.
        $variable = $spv['variable_value'];
        $variable = str_replace(' ', '', $variable); //remove spaces.
        $arr = explode(':', $variable);
        if(!function_exists($arr[0])) {
          //bad function!
          $form_state->setErrorByName('spv][variable_value', $this->t('The function name you provided doesn\'t exist.'));
        }
        
        break;
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    $spv = $form_state->getValue('spv');
    $task['data']['spv'] = array(
      'variable' => $spv['variable'],
      'method' => $spv['method'],
      'variable_value' => $spv['variable_value'],
    );
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    $data = $task['data']['spv'];
    if( (array_key_exists('variable', $data) && $data['variable'] == '')  || !array_key_exists('variable', $data)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The SPV Task has not been set up properly.  The variable you wish to set is missing and thus the engine will be unable to execute this task.'),
      );
    }
    if( (array_key_exists('method', $data) && $data['method'] == '')  || !array_key_exists('method', $data)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The SPV Task has not been set up properly.  The method you wish to set the variable with is missing and thus the engine will be unable to execute this task.'),
      );
    }
    //we can have a blank value.... perhaps not in the form, but certainly in code
    if( !array_key_exists('variable_value', $data)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The SPV Task has not been set up properly.  The variable value you wish to set the variable to is missing and thus the engine will be unable to execute this task.'),
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
