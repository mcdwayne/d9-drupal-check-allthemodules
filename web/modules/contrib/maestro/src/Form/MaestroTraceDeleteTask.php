<?php
namespace Drupal\maestro\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\maestro\Engine\MaestroEngine;

/**
 * The confirm form for deleting a task.
 */
class MaestroTraceDeleteTask extends ConfirmFormBase {

  /**
   * The ID or comma separated list of IDs of the item(s) to delete.
   *
   * @var string
   */
  protected $id;

  
  protected $processID;
  /**
   * {@inheritdoc}.
   */
  public function getFormId()
  {
    return 'maestro_trace_delete_task';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete Queue Item(s) %id?', array('%id' => $this->id));
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
    return $this->t('This will remove the tasks from the queue!  This may cause damage to the executing workflow!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Queue Items Now!');
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
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ids = explode(',', $this->id);
    foreach($ids as $queueID) {
      if($queueID != '') {
        $queueRecord = MaestroEngine::getQueueEntryById($queueID);
        $queueRecord->delete();
      }
    }
    $form_state->setRedirect('maestro.trace', ['processID' => $this->processID]);
  }
}