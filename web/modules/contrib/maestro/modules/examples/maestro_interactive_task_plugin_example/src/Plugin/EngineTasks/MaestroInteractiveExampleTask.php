<?php

namespace Drupal\maestro_interactive_task_plugin_example\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
/**
 * Maestro Interactive Example Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroIntExample",
 *   task_description = @Translation("The Maestro Engine's Interactive Example task."),
 * )
 */
class MaestroInteractiveExampleTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return TRUE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return $this->t('Int Example Task');  //descriptions used however the template builder sees fit.
                                      //If the task name is too long, you could abbreviate it here and use 
                                      //in a template builder UI.
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Interactive Example Task.');  //same as shortDescription, but just longer!  (if need be obviously)
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroIntExample';  //The ID of the plugin.  Should match the @id shown in the annotation.
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#0000ff';  //This is the hex colour code used in the template builder to differentiate tasks from one another.
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
    
    //We set the run_once flag here.  The Run Once flag is located on the queue entity.
    //Interactive and content type tasks are executed and completed by the user using the Maestro API, and not completed by the engine.
    //If we don't set the run_once flag, the engine will simply run through this execute method on each run of the orchestrator.
    //Setting the run_once flag means that the engine will only execute this method the first time after task creation.
    //We don't set this field automatically for interactive tasks as there may be a situation that your 
    //custom task needs to execute something on each engine cycle
    //There's currently no API routine to do this as this is so low-level and task specific that
    //there was no need to do so.
    $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($this->queueID);
    $queueRecord->set('run_once', 1);
    $queueRecord->save();
    return TRUE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    /**
     * For the built in MaestroInteractiveTask, this method will only ever be called if the 'handler' template property is not set.
     * 
     * This task has been set to be a non-interactive task, however, we'll just plunk in a form as an example.
     * 
     * We use the MaestroExecuteInterative class which extends the MaestroInteractiveFormBase class in order to provide
     * a slew of features and functionality to allow for a complete execution of an interactive task.
     */
    
    $form['queueID'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The queue ID of this task'),
      '#default_value' => $this->queueID,
      '#description' => $this->t('queueID'),
    );
    
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Accept'),
    );
    
    $form['actions']['reject'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Reject'),
    );
    
    if($modal == 'modal') {  //if this is a modal task, we use the ajax completion routines and tell the buttons to use our built in completeForm modal closer
      $form['actions']['submit']['#ajax'] = array(
        'callback' => [$parent, 'completeForm'], //you will find this in the MaestroInteractiveFormBase.php file
        'wrapper' => '',
      );
    
      $form['actions']['reject']['#ajax'] = array(
        'callback' => [$parent, 'completeForm'],  //you will find this in the MaestroInteractiveFormBase.php file
        'wrapper' => '',
      );
    }
    return $form;
    
  }
  
  /**
   * {@inheritDoc}
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {
    /**
     * This is the submit handler for the getExecutableForm form.  
     * This method is executed by the MaestroInteractiveFormBase submit handler so you can do whatever it is you need
     * to do to the task here.
     * 
     * In the event you have a handler, that is called instead.
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
    
    //we are forcing the handler, on save, to our stock accept task.
    $task['handler'] = 'maestro_accept_only_form'; //you can find this in the maestro.module file
    
    //since this is an interactive task, it is up to you to either get a handler from the end user
    //OR force it to something specific. 
    
    
    
    //we are also forcing down the modal option so that the task appears as a form
    $task['data']['modal'] = 'modal';  //forcing this to be a modal task means that the $task['handler'] is assumed to be a Drupal Form
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
    
    //we force-set the handler in our prepareTaskForSave method.
    //if for some reason this doesn't get set, we fail validation
    if( (array_key_exists('handler', $task) && $task['handler'] == '')  || !array_key_exists('handler', $task)) {
      $validation_failure_tasks[] = array(
          'taskID' => $task['id'],
          'taskLabel' => $task['label'],
          'reason' => t('The Example Interactive Task handler is missing and thus the engine will fail to show an execute link to the user. Try to edit and resave the task.'),
      );
    }
    
    
    //forcing the modal option to appear as well, so we check for it
    if( (array_key_exists('modal', $task['data']) && $task['data']['modal'] == '')  || !array_key_exists('modal', $task['data'])) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Example Interactive Task modal option is missing. Try to edit and resave the task.'),
      );
    }
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
