<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
/**
 * Maestro And Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroAnd",
 *   task_description = @Translation("The Maestro Engine's And task."),
 * )
 */
class MaestroAndTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('And Task');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('And Task.');
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroAnd';
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#daa520';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the AND task returns TRUE when all pointers are complete and FALSE when still waiting..
   * {@inheritdoc}
   */
  public function execute() {
    //determine who is pointing at me
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);
    $pointers = MaestroEngine::getTaskPointersFromTemplate($templateMachineName, $taskMachineName);
    //now that we have pointers, let's determine if they're all complete 
    //otherwise, return false
    
    $query = \Drupal::entityQuery('maestro_queue');
    
    $andMainConditions = $query->andConditionGroup()
      ->condition('status', '1')
      ->condition('process_id', $this->processID);
    
    $orConditionGroup = $query->orConditionGroup();
    foreach($pointers as $taskID) {
      $orConditionGroup->condition('task_id', $taskID);
    }
    
    $andMainConditions->condition($orConditionGroup);
    $query->condition($andMainConditions);
    
    $queueIdCount = $query->count()->execute();
    
    if(count($pointers) == $queueIdCount) { 
      return TRUE;
    }
    
    return FALSE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {} //we don't do anything in the AND
  
  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {}
  
  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    return array();
  }
  
  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
  
  }
  
  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    //nothing to validate
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    return array('edit', 'drawlineto', 'removelines', 'remove');
  }
}
