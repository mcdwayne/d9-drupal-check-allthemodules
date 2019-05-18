<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
/**
 * Maestro Or Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroOr",
 *   task_description = @Translation("The Maestro Engine's OR task."),
 * )
 */
class MaestroOrTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('Or Task');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Or Task.');
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroOr';
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#daa520';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the Or task returns TRUE and does nothing else.
   * This task, however, is recognized by the engine when executing the REGEN portion of the engine
   * {@inheritdoc}
   */
  public function execute() {
    return TRUE;
  }
  
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
  
  }
  
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
  
  }
  
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
