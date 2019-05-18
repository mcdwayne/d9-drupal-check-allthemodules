<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

/**
 * Maestro If Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroIf",
 *   task_description = @Translation("The Maestro Engine's If task."),
 * )
 */
class MaestroIfTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('If Task');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Performs logic on task completion or variables.');
  }

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroIf';
  }

  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
   return '#daa520';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the Batch Function task will use the handler for this task as the executable function.
   * {@inheritdoc}
   */
  public function execute() {
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskMachineName);
    $statusFlag = NULL;
    $ifData = $task['data']['if'];
    $variable = $ifData['variable'];
    $variableValue = $ifData['variable_value'];
    $method = $ifData['method'];
    $status = $ifData['status']; //this is one of our constants defined in the engine
    $operator = $ifData['operator'];  //  = , !=, < , >
    
    switch($method) {
      case 'byvariable':
        $processVariableValue = MaestroEngine::getProcessVariable($variable, $this->processID);
        $statusFlag = TRUE;  //we're being optimistic here in that we're going to have a status match
        //we need to determine if the variable chosen contains the value we are testing against based on the condition.
        switch($operator) {
          case '=':
            if(strcmp($variableValue, $processVariableValue) != 0) { //not equal!
              $statusFlag = FALSE;
            }
            break;
            
          case '!=':
            if(strcmp($variableValue, $processVariableValue) == 0) { // equal!
              $statusFlag = FALSE;
            }
            break;
            
          case '<':
            if(floatval($processVariableValue) > floatval($variableValue)) {
              $statusFlag = FALSE;
            }
            break;
            
          case '>':
            if(floatval($processVariableValue) < floatval($variableValue)) {
              $statusFlag = FALSE;
            }
            break;
        }
        break;
        
      case 'bylasttaskstatus':
        //need to find out who points to this task.  If there is more than one pointer to this task, 
        //we have no real way to know what to do other than if any other task that DOESN'T have the
        //status, we return false
        $pointers = MaestroEngine::getTaskPointersFromTemplate($templateMachineName, $taskMachineName);
        //pointers now holds the task machine names (taskIDs).  we fetch these from the queue now
        $query = \Drupal::entityQuery('maestro_queue');
        $andMainConditions = $query->andConditionGroup()
          ->condition('process_id', $this->processID)
          //we need to also ignore any statuses that are not 1's
          //otherwise the engine looks at tasks that have been regenerated or outstanding
          ->condition('status', '0', '<>')
          ->condition('archived', '1');
        
        $orConditionGroup = $query->orConditionGroup();
        foreach($pointers as $taskID) {
          $orConditionGroup->condition('task_id', $taskID);
        }
        
        $andMainConditions->condition($orConditionGroup);
        $query->condition($andMainConditions);
        $entity_ids = $query->execute();
        foreach($entity_ids as $entityID) {
          $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($entityID);
          if(strcmp($status, $queueRecord->status->getString()) != 0 ) {
            $statusFlag = FALSE;
          }
        }
        //at this point, if the statusFlag is not false, it must be OK as the default is NULL
        if($statusFlag === NULL) $statusFlag = TRUE;
        break;
    }
    //at this point, we have a statusFlag variable that denotes whether the pointed-from tasks in the queue have
    //a status that is equal to the status that was provided in the template.  If it's false, then we complete this
    //IF task with the execution status as success with a completion status of use the false branch.
    if($statusFlag !== NULL) {
      if($statusFlag == TRUE) {
        $this->executionStatus = TASK_STATUS_SUCCESS;  //normal condition here. we've not aborted or done anything different
        $this->completionStatus = MAESTRO_TASK_COMPLETION_NORMAL;  //this will follow the true branch
      }
      else {
        $this->executionStatus = TASK_STATUS_SUCCESS;  //again, nothing unusual.  just set the task status
        $this->completionStatus = MAESTRO_TASK_COMPLETION_USE_FALSE_BRANCH; //ahh.. last status doesn't equal what we tested for.  Use the false branch for nextstep
      }
      return TRUE;  //nothing really stopping us from always completing this task in the engine.
    }
    
    \Drupal::logger('maestro')->error('If task does not have a statusFlag set and is unable to complete');
    return FALSE; // problem here - we have a situation where the IF statement is stuck because the statusFlag was never set.
    //this will hold the engine at the IF task forever.
  }
  
  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    
  }
  
  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    $ifParms = isset($task['data']['if']) ? $task['data']['if'] : []; 
    
    
    $form = array(
      '#markup' => $this->t('Edit the logic for this IF task'),  
    );
    
    $form['method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Execute the IF:'),
      '#options' => array(
          'byvariable' => $this->t('By Variable'),
          'bylasttaskstatus' => $this->t('By Last Task Status'),
      ),
      '#default_value' => isset($ifParms['method']) ? $ifParms['method'] : '',
      '#required' => TRUE,
      '#attributes' => [
          //'onclick' => 'document.getElementById("byvar").setAttribute("open", "open");'
          'onclick' => 'maestro_if_task_toggle(this);'
      ],
      '#attached' => [
        'library' => array('maestro/maestro-engine-task-edit'),
      ],
    );
    
    /*
     * By Variable options
     */
    
    $variables = MaestroEngine::getTemplateVariables($templateMachineName);
    $options = array();
    foreach($variables as $variableName => $arr) {
      $options[$variableName] = $variableName;
    }
    
    $form['byvariable'] = array(
      '#id' => 'byvar',
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('By Variable Options'),
      '#open' => FALSE,
    );
    
    $form['byvariable']['variable'] = array(
      '#type' => 'select',
      '#title' => $this->t('Argument variable'),
      '#required' => FALSE,
      '#default_value' => isset($ifParms['variable']) ? $ifParms['variable'] : '',
      '#options' => $options,
      
    );
    
    $form['byvariable']['operator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#required' => FALSE,
      '#default_value' => isset($ifParms['operator']) ? $ifParms['operator'] : '',
      '#options' => array(
          '=' => '=',
          '>' => '>',
          '<' => '<',
          '!=' => '!=',
      ),
    );
    
    $form['byvariable']['variable_value']  = array(
      '#type' => 'textfield',
      '#title' => $this->t('Variable value'),
      '#description' => $this->t('The IF will check against this value during execution'),
      '#default_value' => isset($ifParms['variable_value']) ? $ifParms['variable_value'] : '',
      '#required' => FALSE,
    );

    
    
    /*
     * The by status section
     */
    $form['bystatus'] = array(
      '#id' => 'bystatus',
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('By Last Task Status'),
      '#open' => FALSE,
      '#markup' => $this->t('This method is only useful if ONLY ONE task points to this IF. 
          If more than one task points to this IF task, a FALSE will be returned if ANY of those 
          tasks do not have a status of the status chosen in the status selector.'),
    );
    
    $form['bystatus']['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#required' => FALSE,
      '#default_value' => isset($ifParms['status']) ? $ifParms['status'] : '',
      '#options' => array(
          TASK_STATUS_SUCCESS => $this->t('Last Task Status is Success'),
          TASK_STATUS_CANCEL => $this->t('Last Task Status is Cancel'),
          TASK_STATUS_HOLD => $this->t('Last Task Status is Hold'),
          TASK_STATUS_ABORTED => $this->t('Last Task Status is Aborted'),
      ),
    );
    
    if(isset($ifParms['method']) && $ifParms['method'] == 'byvariable') {
      $form['byvariable']['#open'] = TRUE;
      $form['bystatus']['#open'] = FALSE;
    }
    else {
      $form['byvariable']['#open'] = FALSE;
      $form['bystatus']['#open'] = TRUE;
    }
    
    return $form;
  }
  
  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
    $method = $form_state->getValue('method');
    switch($method) {
      case 'byvariable':
        $byvars = $form_state->getValue('byvariable');
        if(empty($byvars['variable'])) {
          $form_state->setErrorByName('byvariable][variable', $this->t('When doing an IF by variable, you must provide a variable to IF on.'));
        }
        if(empty($byvars['operator'])) {
          $form_state->setErrorByName('byvariable][operator', $this->t('When doing an IF by variable, you must provide a operator.'));
        }
        if(empty($byvars['variable_value'])) {
          $form_state->setErrorByName('byvariable][variable_value', $this->t('When doing an IF by variable, you must provide a variable value.'));
        }
        $form['byvariable']['#open'] = TRUE;
        $form['bystatus']['#open'] = FALSE;
        break;
        
      case 'bystatus':
        //this condition may not even occur, but if for some reason the form is corrupt, we need to ensure we have a value.
        $byvars = $form_state->getValue('bystatus');
        if(empty($byvars['status'])) {
          $form_state->setErrorByName('bystatus][status', $this->t('When doing an IF by statys, you must provide a status value.'));
        }
        $form['byvariable']['#open'] = FALSE;
        $form['bystatus']['#open'] = TRUE;
        break;
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    //variable, operator, variable_value, status
    $method = $form_state->getValue('method');
    $byvariable = $form_state->getValue('byvariable');
    $bystatus = $form_state->getValue('bystatus');
    $task['data']['if'] = array(
      'method' => $method, 
      'variable' => $byvariable['variable'],
      'operator' => $byvariable['operator'],
      'variable_value' => $byvariable['variable_value'],
      'status' => $bystatus['status'],
    );
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    //we have a number of fields that we know MUST be filled in.
    //the issue is that we have a to and falseto branches that we really don't know if they should be connected or not
    //so for the time being, we'll leave the to and falseto branches alone
    $data = $task['data']['if'];
    //first check the method.  if it's blank, the whole thing will simply fail out
    if( (array_key_exists('method', $data) && $data['method'] == '')  || !array_key_exists('method', $data)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The IF task has not been set up properly.  The method of "By Variable" or "By Status" is missing and thus the engine will be unable to execute this task.'),
      );
    }
    else {
      //method IS filled in.  Let's validate the rest now
      //operator is important for both
      if( (array_key_exists('operator', $data) && $data['operator'] == '')  || !array_key_exists('operator', $data)) {
        $validation_failure_tasks[] = array(
          'taskID' => $task['id'],
          'taskLabel' => $task['label'],
          'reason' => t('The IF task has not been set up properly. The operator has not been set. The engine will be unable to execute this task.'),
        );
      }
      
      switch($data['method']) {
        case 'byvariable':
          //check that the variable has been set
          if( (array_key_exists('variable', $data) && $data['variable'] == '')  || !array_key_exists('variable', $data)) {
            $validation_failure_tasks[] = array(
              'taskID' => $task['id'],
              'taskLabel' => $task['label'],
              'reason' => t('The IF task has not been set up properly. The variable has not been set. The engine will be unable to execute this task.'),
            );
          }
          //it is conceivable that the variable value could be tested against a blank.  So just make sure the value key exists
          if( !array_key_exists('variable_value', $data)) {
            $validation_failure_tasks[] = array(
              'taskID' => $task['id'],
              'taskLabel' => $task['label'],
              'reason' => t('The IF task has not been set up properly. The variable value has not been set. The engine will be unable to execute this task.'),
            );
          }
          break;
          
        case 'bystatus':
          if( (array_key_exists('status', $data) && $data['status'] == '')  || !array_key_exists('status', $data)) {
            $validation_failure_tasks[] = array(
              'taskID' => $task['id'],
              'taskLabel' => $task['label'],
              'reason' => t('The IF task has not been set up properly. The status has not been set. The engine will be unable to execute this task.'),
            );
          }
          break;
      }
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    return array('edit', 'drawlineto', 'drawfalselineto', 'removelines', 'remove');
  }
}
