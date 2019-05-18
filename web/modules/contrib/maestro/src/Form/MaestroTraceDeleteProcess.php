<?php
namespace Drupal\maestro\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\maestro\Engine\MaestroEngine;

/**
 * The confirm form for deleting a process.
 */
class MaestroTraceDeleteProcess extends ConfirmFormBase {

  /**
   * The ID or comma separated list of IDs of the item(s) to delete.
   *
   * @var string
   */
  protected $id;

  protected $processID;

  protected $templateName;
  /**
   * {@inheritdoc}.
   */
  public function getFormId()
  {
    return 'maestro_trace_delete_process';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete this instance (Process: %pid) of the workflow: %template?', array('%pid' => $this->processID, '%template' => $this->templateName));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('maestro.trace', ['processID' => $this->processID]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will remove all the tasks and the process from the queue!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Queue Items Now and process records now!');
  }


  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $id
   *   (optional) This is the discrete ID or the list of task IDs to delete
   */
  public function buildForm(array $form, FormStateInterface $form_state, $processID = NULL, $idList = NULL) {
    $this->id = $idList;
    $this->processID = $processID;
    $this->templateName = MaestroEngine::getTemplateIdFromProcessId($processID);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    MaestroEngine::deleteProcess($this->processID);

    drupal_set_message('Process and Task history successfully deleted');
    $form_state->setRedirect('view.maestro_outstanding_tasks.maestro_outstanding_tasks');
  }
}