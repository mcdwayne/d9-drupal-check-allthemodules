<?php

namespace Drupal\maestro_noninteractive_task_plugin_example\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
/**
 * Maestro Non Interactive Example Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroNonIntExample",
 *   task_description = @Translation("The Maestro Engine's Non Interactive Example task."),
 * )
 */
class MaestroNonInteractiveExampleTask extends PluginBase implements MaestroEngineTaskInterface {

  use MaestroTaskTrait;  //please see the \Drupal\maestro\MaestroTaskTrait for details on what's included  
  
  /**
   * 
   * @param array $configuration
   *   The incoming configuration information from the engine execution.
   *   [0] - is the process ID
   *   [1] - is the queue ID
   *   The processID and queueID properties are defined in the MaestroTaskTrait.
   */
  function __construct(array $configuration = NULL) {
    if(is_array($configuration)) {
      $this->processID = $configuration[0];
      $this->queueID = $configuration[1];
    }
  }
  
  /**
   * {@inheritDoc}
   */
  public function isInteractive() {
    /*
     * You would return a TRUE here if this task required manual human intervention to complete.
     * This only applies to a task that must present options to the end user for completion in a task console.
     * For this example, we return FALSE.  
     * 
     * See the MaestroInteractiveTask type code for how to return an interactive task.
     */
    return FALSE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('Non-Int Example Task');  //descriptions used however the template builder sees fit.
                                      //If the task name is too long, you could abbreviate it here and use 
                                      //in a template builder UI.
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Non Interactive Example Task.');  //same as shortDescription, but just longer!  (if need be obviously)
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroNonIntExample';  //The ID of the plugin.  Should match the @id shown in the annotation.
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#606060';  //This is the hex colour code used in the template builder to differentiate tasks from one another.
                       //The colour chosen here is purely for example purposes.
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the Example task returns TRUE and does nothing else.
   * {@inheritdoc}
   */
  public function execute() {
    /**
     * You can refer to other Maestro task types, however, in this execute method you must do any of the heavy
     * lifting required by the task to complete.
     * 
     * Returning TRUE tells the engine you've completed execution properly and the task is complete.
     * Return a FALSE to not tell the engine to archive and flag the task as complete.
     */
    return TRUE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    /**
     * This task has been set to be a non-interactive task, therefore we do not need to return a form
     */
    
  }
  
  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
    /**
     * We don't need to do anything in this submit handler as we do not have any executable interface.
     */
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskEditForm(array $task, $templateMachineName) {
    /**
     * If you require any additional form elements, you manage those in here.
     * Return a Form API array.
     */
    return array();
  }
  
  /**
   * {@inheritDoc}
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {
  /**
   * Need to validate anything on your edit form?  Do that here.
   */
  }
  
  /**
   * {@inheritDoc}
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {
    /**
     * Do you need to massage the edited and saved data in for this task before it is saved to the template?
     * This is where you do that.  Generally you'd place task data into the 'data' property of the template as shown
     * in the example here:
     * 
     * $task['handler'] = $form_state->getValue('handler');  //if you have a handler field, this is how you'd populate the task with it
     * $task['data']['my_field'] = ...do some work here....;
     * 
     */
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    /**
     * When you use a task in the template builder, it will be up to the task to provide any sort of debugging and validation
     * information to the end user.  Do you have a field that MUST be set in order for the task to execute?
     * How about a field that doesn't have the right values?  This is where you would populate the 
     * $validation_failure_tasks array with failure information and the 
     * $validation_information_tasks with informational messages.
     * 
     * See the MaestroEngineTaskInterface.php interface declaration of this method for details.
     */
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTemplateBuilderCapabilities() {
    /**
     * This method is used by the template builder to signal to the UI what this task can or cannot do.
     * Look at the function declaration for more info and other tasks for what they return.
     * Generally, the capabilities listed here are the general four that a task should be able to accomodate.
     */
    return array('edit', 'drawlineto', 'removelines', 'remove');
  }
}
