<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

/**
 * Maestro End Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroEnd",
 *   task_description = @Translation("The Maestro Engine's end task."),
 * )
 */
class MaestroEndTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('End Task');
  }
  
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('End Task.');
  }

  /**
   * 
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroEnd';
  }
    
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#ff0000';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the End task will complete the process and return true so the engine completes the task.
   * {@inheritdoc}
   */
  public function execute() {
    if($this->processID >0) {
      MaestroEngine::endProcess($this->processID);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
  
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    
  }
  
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  
  }
  
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = array(
        '#markup' => t(''),
    );
    return $form;
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
    //nothing to check
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    return array('edit', 'removelines', 'remove');
  }
}
