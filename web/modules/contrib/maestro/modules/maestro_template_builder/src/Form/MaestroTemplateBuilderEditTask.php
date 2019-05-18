<?php


namespace Drupal\maestro_template_builder\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\maestro_template_builder\Ajax\FireJavascriptCommand;


class MaestroTemplateBuilderEditTask extends FormBase {

  public function getFormId() {
    return 'template_edit_task';
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    //TODO:  we should be passing validation off to the tasks as well
    $templateMachineName = $form_state->getValue('template_machine_name');
    $taskID = $form_state->getValue('task_id');
    $template = MaestroEngine::getTemplate($templateMachineName);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskID);
    $executableTask = MaestroEngine::getPluginTask($task['tasktype']);
    
    $executableTask->validateTaskEditForm($form, $form_state);
  }

  public function cancelForm(array &$form, FormStateInterface $form_state) {
    //we cancel the modal dialog by first sending down the form's error state as the cancel is a submit.
    //we then close the modal
    $response = new AjaxResponse();
    $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
    ];
    $_SESSION['maestro_template_builder']['maestro_editing_task'] = '';  //remove the session variable for the task being edited
    $response->addCommand(new HtmlCommand('#edit-task-form', $form));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {  //if we have errors in the form, show those
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response = new AjaxResponse();
      $response->addCommand(new HtmlCommand('#edit-task-form', $form)); //replaces the form HTML with the validated HTML
      return $response;
    }
    else {  //otherwise, we can get on to saving the task
      //this should be managed by the engine.  in here for the time being.
    
      $templateMachineName = $form_state->getValue('template_machine_name');
      $taskID = $form_state->getValue('task_id');
      $template = MaestroEngine::getTemplate($templateMachineName);
      $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskID);
      $executableTask = MaestroEngine::getPluginTask($task['tasktype']);
    
      //first, lets let the task do any specific or unique task preparations
      $executableTask->prepareTaskForSave($form, $form_state, $task);  //prepares any specific pieces of the task for us
      //now the core maestro requirements like the assignments and notifications
      $result = $executableTask->saveTask($form, $form_state, $task);
      if($result === FALSE) {  //Oh Oh.  Some sort of error in saving the template.
        drupal_set_message($this->t('Error saving your task.'), 'error');
        $form['status_messages'] = [
            '#type' => 'status_messages',
            '#weight' => -10,
        ];
     }
    }
    
    $form_state->setRebuild(TRUE);  //rebuild the form to get an updated table of assignment information
  }
  
  public function saveForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {  //if we have errors in the form, show those
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
          '#type' => 'status_messages',
          '#weight' => -10,
      ];
      $response = new AjaxResponse();
      $response->addCommand(new HtmlCommand('#edit-task-form', $form)); //replaces the form HTML with the validated HTML
      return $response;
    }
    //save of the task has already been done in the submit.  We now are only responsible for updating the UI and updating the form.  
    $templateMachineName = $form_state->getValue('template_machine_name');
    $taskID = $form_state->getValue('task_id');
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskID);
    
    $update = [
      'label' => $task['label'], 
      'taskid' => $task['id'], 
      'body' => 'placeholder',
      'participate_in_workflow_status_stage' => $task['participate_in_workflow_status_stage'],
      'workflow_status_stage_number' => $task['workflow_status_stage_number'],
      'workflow_status_stage_message' => $task['workflow_status_stage_message'],
    ];
    
    $response = new AjaxResponse();
    $response->addCommand(new FireJavascriptCommand('maestroUpdateMetaData', $update));
    $response->addCommand(new HtmlCommand('#edit-task-form', $form));
    $response->addCommand(new FireJavascriptCommand('maestroShowSavedMessage', []));
    return $response;
  }
  
  /**
   * ajax callback for add-new-form button click
   */
  public function buildForm(array $form, FormStateInterface $form_state, $templateMachineName = '') {
    $taskID = Xss::filter($_SESSION['maestro_template_builder']['maestro_editing_task']);  
    $template = MaestroEngine::getTemplate($templateMachineName);
    $task = MaestroEngine::getTemplateTaskByID($templateMachineName, $taskID);
    $task['form_state'] = $form_state;
    //need to validate this taskID and template to ensure that they exist
    if($taskID == '' || $template == NULL || $task == NULL) {
      $form = array(
        '#title' => t('Error!'),
        '#markup' => t('The task or template you are attempting to edit does not exist'),
      );
      return $form;
    }
    
    $form = array(
      '#title' => $this->t('Editing Task: ') . $task['label'] . '(' . $taskID . ')',
      '#prefix' => '<div id="edit-task-form">',
      '#suffix' =>'</div>',
    );

    $form['save_task_notification'] = array (
      '#markup' => $this->t('Task Saved'),
      '#prefix' => '<div id="save-task-notificaiton" class="messages messages--status">',
      '#suffix' => '</div>',
    );
    //get a handle to the task plugin
    $executableTask = MaestroEngine::getPluginTask($task['tasktype']);
    
    //get the base edit form that all tasks adhere to
    $form += $executableTask->getBaseEditForm($task, $templateMachineName);
    
    //we now will pull back the edit form provided to us by the task itself.
    //this gives ultimate flexibility to developers.
    //even form alters work on this form by allowing the dev to detect what task_id is being edited
    //and get the task type and do any modifications on it from there
    $form += $executableTask->getTaskEditForm($task, $templateMachineName);
    
    //now is this thing interactive or not?
    //if so, we show the assignment and notification tabs.  If not, leave it out
    if($executableTask->isInteractive()) {   
      $form += $executableTask->getAssignmentsAndNotificationsForm($task, $templateMachineName);
    }
    
    //save button in an actions bar:
    $form['actions'] = array(
      '#type' => 'actions',
    );
    
    $form['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Task'),
      '#required' => TRUE,
      '#ajax' => array(
          'callback' => [$this, 'saveForm'], //use saveFrom rather than submitForm to alleviate the issue of calling a save handler twice
          'wrapper' => '',
      ),
    );
    
    $form['actions']['close'] = array(
      '#type' => 'button',
      '#value' => $this->t('Close'),
      '#required' => TRUE,
      '#ajax' => array(
          'callback' => [$this, 'cancelForm'],
          'wrapper' => '',
      ),
    );
    return $form;
  }
}


