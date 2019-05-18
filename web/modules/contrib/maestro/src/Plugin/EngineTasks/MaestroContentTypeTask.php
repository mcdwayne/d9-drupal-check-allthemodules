<?php

namespace Drupal\maestro\Plugin\EngineTasks;

use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;
/**
 * Maestro Content Type Task Plugin.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroContentType",
 *   task_description = @Translation("The Maestro Engine's content type task."),
 * )
 */
class MaestroContentTypeTask extends PluginBase implements MaestroEngineTaskInterface {

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
    return t('Content Type Task');
  }
  
  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Content Type Task.');
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroContentType';
  }
  
  /**
   * {@inheritDoc}
   */
  public function getTaskColours() {
    return '#0000ff';
  }
  
  /*
   * Part of the ExecutableInterface
   * Execution of the interactive task does nothing except for setting the run_once flag
   * {@inheritdoc}
   */
  public function execute() {
    //need to set the run_once flag here
    //as interactive and content type tasks are executed and completed by the user using the Maestro API
    $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($this->queueID);
    $queueRecord->set('run_once', 1);
    $queueRecord->save();
    
    return FALSE;
  }
  
  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
    //we'll use the handler to determine what to do here.
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
    $form = array(
      '#markup' => t('Content Type Task Edit'),
    );
    
    $form['content_type'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Content type'),
      '#description' => $this->t('The content type for this task to use.'),
      '#default_value' => isset($task['data']['content_type']) ? $task['data']['content_type'] : '',
      '#required' => TRUE,
    );
    
    $form['unique_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Give this piece of content a unique identifier'),
      '#description' => $this->t('This identifier is stored along with its ID to allow you to recall it when filled in.'),
      '#default_value' => isset($task['data']['unique_id']) ? $task['data']['unique_id'] : '',
      '#required' => TRUE,
    );
    
    $form['save_edit_later'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show a Save and Edit Later button on the content type form.'),
      '#description' => $this->t('The Save and Edit Later button saves the content to the entity identifiers and the task remains uncompleted.'),
      '#default_value' => isset($task['data']['save_edit_later']) ? $task['data']['save_edit_later'] : 0,
      '#required' => FALSE,
    );
    
    $form['link_to_edit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the Edit page of the content in the task console.'),
      '#description' => $this->t('When checked, the edit node page will be shown.  Unchecked means the view page is shown.'),
      '#default_value' => isset($task['data']['link_to_edit']) ? $task['data']['link_to_edit'] : 0,
      '#required' => FALSE,
    );
    
    $form['show_maestro_buttons_on_view'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the Maestro Accept and Reject buttons when viewing a content type.'),
      '#description' => $this->t('When checked, the accept and reject buttons will appear when viewing a content type task. The Accept and Reject labels can be overridden.'),
      '#default_value' => isset($task['data']['show_maestro_buttons_on_view']) ? $task['data']['show_maestro_buttons_on_view'] : 0,
      '#required' => FALSE,
    );
    
    
    $form['view_buttons'] = array (
      '#type' => 'fieldset',
      '#states' => array(
        'visible' => array(
          ':input[name="show_maestro_buttons_on_view"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $form['view_buttons']['accept_label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Accept Label'),
      '#description' => $this->t('The label applied to the accept button when viewing the content type managed by this task.'),
      '#default_value' => isset($task['data']['accept_label']) ? $task['data']['accept_label'] : '',
      '#size' => 15,
      '#states' => array(
        'visible' => array(
          ':input[name="show_maestro_buttons_on_view"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $form['view_buttons']['accept_redirect_to'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Accept Redirect To Location'),
      '#description' => $this->t('The URL location to redirect to when the accept button is pressed.'),
      '#default_value' => isset($task['data']['accept_redirect_to']) ? $task['data']['accept_redirect_to'] : '',
      '#states' => array(
        'visible' => array(
          ':input[name="show_maestro_buttons_on_view"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $form['view_buttons']['reject_label'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($task['data']['reject_label']) ? $task['data']['reject_label'] : '',
      '#title' => $this->t('Reject Label'),
      '#size' => 15,
      '#description' => $this->t('The label applied to the reject button when viewing the content type managed by this task.'),'#states' => array(
        'visible' => array(
          ':input[name="show_maestro_buttons_on_view"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $form['view_buttons']['reject_redirect_to'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reject Redirect To Location'),
      '#description' => $this->t('The URL location to redirect to when the reject button is pressed.'),
      '#default_value' => isset($task['data']['reject_redirect_to']) ? $task['data']['reject_redirect_to'] : '',
      '#states' => array(
        'visible' => array(
          ':input[name="show_maestro_buttons_on_view"]' => array('checked' => TRUE),
        ),
      ),
    );
    
    $form['view_buttons']['supply_maestro_ids_in_url'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Supply the maestro=1 and queueid=xxx URL parameters for the accept and reject buttons.'),
      '#description' => $this->t('When checked, the URL redirected to for accept or reject will have the Maestro url parameters embedded in it.'),
      '#default_value' => isset($task['data']['supply_maestro_ids_in_url']) ? $task['data']['supply_maestro_ids_in_url'] : 0,
      '#required' => FALSE,
    );
        
    $form['redirect_to'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default Return Path'),
      '#description' => $this->t('When saving a new piece of content or when viewing content that does not have the accept or reject redirect paths set, you can specify where your return path should go upon task completion.'),
      '#default_value' => isset($task['data']['redirect_to']) ? $task['data']['redirect_to'] : 'taskconsole',
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
    $task['data']['unique_id'] = $form_state->getValue('unique_id');
    $task['data']['content_type'] = $form_state->getValue('content_type');
    $task['data']['save_edit_later'] = $form_state->getValue('save_edit_later');
    $task['data']['link_to_edit'] = $form_state->getValue('link_to_edit');
    $task['data']['show_maestro_buttons_on_view'] = $form_state->getValue('show_maestro_buttons_on_view');
    $task['data']['accept_label'] = $form_state->getValue('accept_label');
    $task['data']['reject_label'] = $form_state->getValue('reject_label');
    $task['data']['accept_redirect_to'] = $form_state->getValue('accept_redirect_to');
    $task['data']['reject_redirect_to'] = $form_state->getValue('reject_redirect_to');
    $task['data']['supply_maestro_ids_in_url'] = $form_state->getValue('supply_maestro_ids_in_url');
    
    $redirect = $form_state->getValue('redirect_to');
    if(isset($redirect)) {
      $task['data']['redirect_to'] = $redirect;
    }
    else {
      $task['data']['redirect_to'] = '';
    }
    
    //we create our own handler here based on the content type and signify that it is maestro based
    //This is the NODE ADD handler.  We alter this in a post-assignment hook if the entity identifier already exists in maestro.module
    //see maestro_maestro_post_production_assignments
    $task['handler'] = '/node/add/' . $form_state->getValue('content_type') . '?maestro=1'; 
    
  }
  
  /**
   * {@inheritDoc}
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
    if( (array_key_exists('unique_id', $task['data']) && $task['data']['unique_id'] == '')  || !array_key_exists('unique_id', $task['data'])) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Content Type Task has not been set up properly.  The "unique identifier" option is missing and thus the engine will be unable to execute this task.'),
      );
    }
    
    if( (array_key_exists('content_type', $task['data']) && $task['data']['content_type'] == '')  || !array_key_exists('content_type', $task['data'])) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Content Type Task has not been set up properly.  The "content type" option is missing and thus the engine will be unable to execute this task.'),
      );
    }
    
    if( (array_key_exists('redirect_to', $task['data']) && $task['data']['redirect_to'] == '')  || !array_key_exists('redirect_to', $task['data'])) {
      $validation_failure_tasks[] = array(
        'taskID' => $task['id'],
        'taskLabel' => $task['label'],
        'reason' => t('The Content Type Task has not been set up properly.  The "Return Path" option is missing and thus the engine will be unable to execute this task.'),
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
