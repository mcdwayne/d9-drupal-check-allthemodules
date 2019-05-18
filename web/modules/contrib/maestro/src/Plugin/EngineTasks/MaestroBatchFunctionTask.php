<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

/**
 * Maestro Batch Function Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroBatchFunction",
 *   task_description = @Translation("The Maestro Engine's Batch Function task."),
 * )
 */
class MaestroBatchFunctionTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('Batch Function');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Batch Function.');
  }

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroBatchFunction';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
   return '#707070';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the Batch Function task will use the handler for this task as the executable function.
   * The handler must return TRUE in order for this function to be completed by the engine.
   * We simply pass the return boolean value back from the called handler to the engine for processing.
   * {@inheritdoc}
   */
  public function execute() {
    $returnValue = FALSE;
    $returnStatus = FALSE;
    $queueRecord = MaestroEngine::getQueueEntryById($this->queueID);
    if($queueRecord) {
      //pick off the handler here and call the code via the user func array
      
      if($queueRecord->handler != NULL) {
        $handler = $queueRecord->handler->getString();
        if(function_exists($handler)) {
          $returnStatus = call_user_func_array($handler, array($this->processID, $this->queueID));
          //lets see if the return status is an array.  if so, we will check if it has any established structure to set status codes
          if(is_array($returnStatus)) {
            if(array_key_exists('completion_status', $returnStatus)) {
              $this->completionStatus = $returnStatus['completion_status'];
            }
            
            if(array_key_exists('execution_status', $returnStatus)) {
              $this->executionStatus = $returnStatus['execution_status'];
            }
            
            if(array_key_exists('status', $returnStatus)) {
              $returnValue = $returnStatus['status'];  //on false, this holds the engine at this task
            }
            
          }
          else {  //not an array.. single value.  set the returnValue with the returnStatus
            $returnValue = $returnStatus;
          }
        }
      }
      else {
        //just do a NOOP here
        $returnValue = TRUE;
      }
      
    }
    return $returnValue;  //true or false to complete the task
  }
  
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
   
  }
  
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  
  }
  
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = array(
      '#markup' => t('Batch Function Edit'),
    );

    //let modules signal the handlers they wish to share
    $handlers = \Drupal::moduleHandler()->invokeAll('maestro_batch_handlers', array());
    $handler_desc = $this->t('The batch function name you wish to call.');
    if(isset($task['handler']) && isset($handlers[$task['handler']])) {
      $handler_desc = $handlers[$task['handler']];
    }
    
    $form['handler'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Handler'),
      '#default_value' => isset($task['handler']) ? $task['handler'] : '',
      '#required' => TRUE,
      '#autocomplete_route_name' => 'maestro.autocomplete.batch_handlers',
      '#ajax' => [
        'callback' => [$this, 'batchFunctionHandlerCallback'],
        'event' => 'autocompleteclose',
        'wrapper' => 'handler-ajax-refresh-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    );

    $form['handler_help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $handler_desc,
      '#readonly' => TRUE,
      '#attributes' => [
        'class' => ['handler-help-message'],
        'id' => ['handler-ajax-refresh-wrapper'],
      ],
    ];

    
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
  public function batchFunctionHandlerCallback(array &$form, FormStateInterface $form_state) {
    $selected_handler = $new_objective_id = $form_state->getValue('handler');

    //let modules signal the handlers they wish to share
    $handlers = \Drupal::moduleHandler()->invokeAll('maestro_batch_handlers', array());
    if($selected_handler != '' && !function_exists($selected_handler)) {
      $handler_desc = \Drupal::translation()->translate('This handler form function does not exist.');
    }
    elseif(isset($handlers[$selected_handler])) {
      $handler_desc = $handlers[$selected_handler];
    }
    else {
      $handler_desc = \Drupal::translation()->translate('The batch function name you wish to call.');
    }

    $form['handler_help_text'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $handler_desc,
      '#readonly' => TRUE,
      '#attributes' => [
        'class' => ['handler-help-message'],
        'id' => ['handler-ajax-refresh-wrapper'],
      ],
    ];

    return $form['handler_help_text'];

  }



  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
    $handler = $form_state->getValue('handler');
    //Let's validate the handler here to ensure that it actually exists.
    if(!function_exists($handler)) {
      $form_state->setErrorByName('handler', $this->t('This handler batch function does not exist.'));
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    $task['handler'] = $form_state->getValue('handler');
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    //so we know that we need a few keys in this $task array to even have a batch function run properly.
    //namely the handler
    
    if( (array_key_exists('handler', $task) && $task['handler'] == '')  || !array_key_exists('handler', $task)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The handler for the task has not been set. This will cause a failure of the engine to execute'),
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
