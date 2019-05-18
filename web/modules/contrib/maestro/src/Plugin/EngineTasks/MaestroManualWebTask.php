<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

/**
 * Maestro Manual Web Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroManualWeb",
 *   task_description = @Translation("The Maestro Engine's Manual Web task."),
 * )
 */
class MaestroManualWebTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return TRUE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return t('Manual Web Task');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Manual Web Task.');
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroManualWeb';
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#0000ff';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the Manual Web sets the runonce key.  It is up to the manual web task
   * consumer to complete this task
   * {@inheritdoc}
   */
  public function execute() {
    $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($this->queueID);
    $queueRecord->set('run_once', 1);
    $queueRecord->save();
    
    return FALSE;
  }
  
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
  
  }
  
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  
  }
  
  public function getTaskEditForm(array $task, $templateMachineName) {
    $form = array(
      '#markup' => t('Interactive Task Edit'),
    );
    
    $form['handler'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Handler'),
      '#description' => $this->t('The web location for this task to be sent to. This can be an external site prefixed with http:// or internal link.'),
      '#default_value' => $task['handler'],
      '#required' => TRUE,
    );

    $form['modal'] = array( //we force this to not modal to let the task consoles create an outbound link
      '#type' => 'hidden',
      '#default_value' => 'notmodal',
      '#value' => 'notmodal',
      '#required' => TRUE,
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
    $task['handler'] = $form_state->getValue('handler');
    $task['data']['modal'] = $form_state->getValue('modal');
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    if( (array_key_exists('handler', $task) && $task['handler'] == '')  || !array_key_exists('handler', $task)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Manual Web Task has not been set up properly.  The handler is missing and thus the engine will be unable to execute this task.'),
      );
    }
    if( (array_key_exists('modal', $task['data']) && $task['data']['modal'] == '')  || !array_key_exists('modal', $task['data'])) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Manual Web Task has not been set up properly.  The modal option is missing.  
                      This is a critical error with the task that you are unable to fix with the UI.  
                      Please try to remove the task and add it back into your workflow.'),
      );
    }
    
    //This task should have assigned users
    //$task['assigned'] should have data
    if( (array_key_exists('assigned', $task) && $task['assigned'] == '')  || !array_key_exists('assigned', $task)) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Manual Web Task has not been set up properly.  The Manual Web Task requires assignments to actors, roles or other assignment options.'),
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
