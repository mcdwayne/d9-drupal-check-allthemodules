<?php

namespace Drupal\maestro;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Form\MaestroExecuteInteractive;

interface MaestroEngineTaskInterface extends ExecutableInterface, PluginInspectionInterface {
  
  /**
   * Returns TRUE or FALSE to denote if this task has an interactive interface that needs to be shown in the Task Console
   * and for any other requirements of the task.
   */
  public function isInteractive();
  
  /**
   * Get the task's short description.  Useful for things like labels
   */
  public function shortDescription();
  
  /**
   * Longer description.  This generally follows the short Description but can be used to be more descriptive if you 
   * wish to surface this description in a UI element
   */
  public function description();

  /**
   * Returns the task's defined colours.  This is useful if you want to let the tasks decide on what colours to paint themselves in the UI
   */
  public function getTaskColours();
  
  /**
   * @return Array Must return form declaration fields if this task is interactive or not.
   * 
   * @param string $modal
   *   defines if the form is a modal form or not.  
   * @param Drupal\maestro\Form\MaestroExecuteInteractive $parent
   *   parent class for using modal callbacks to the interactive form base if needed.
   *  
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent);
  
  /**
   * Interactive tasks, or tasks that signal themselves as requiring human interaction will have the resulting form submissions
   * sent to their own handler for processing to determine if the task should be completed or not or to carry out any task
   * processing that may have to be done.
   * 
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function handleExecuteSubmit(array &$form, FormStateInterface $form_state);
  
  /**
   * Method to allow a task to add their own fields to the task edit form
   * 
   * @param array $task  This is the array representation of the task from the configuration entity
   * 
   * @return Array Must return form declaration fields for the task editor
   */
  public function getTaskEditForm(array $task, $templateMachineName);
  
  /**
   * This method must be called by the template builder in order to validate the form entry values before saving.
   * 
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateTaskEditForm(array &$form, FormStateInterface $form_state);
  
  /**
   * The specific task's manipulation of the values to save for a template save.
   * @param array $form
   * @param FormStateInterface $form_state
   * @param array $task  The fully loaded task array from the template
   */
  public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task);
  
  /**
   * Lets the task perform validation on itself.  If the task is missing any internal requirements, it can flag itself as having an issue.
   * Return array MUST be in the format of array(
   *   'taskID' =>  the task machine name, 
   *   'taskLabel' => the human readable label for the task, 
   *   'reason' => the reason for the failure
   *   )
   * @param array $validation_failure_tasks   The array of other validation failures
   * @param array $validation_information_tasks The array of informational messages
   * @param array $task  The passed-in fully-loaded task from the template (array)
   */
  public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task);
  
  /**
   * Returns an array of consistenly keyed array elements that define what this task can do in the template builder.
   * Elements are:
   * edit, drawlineto, drawfalselineto, removelines, remove
   */
  public function getTemplateBuilderCapabilities();
}
