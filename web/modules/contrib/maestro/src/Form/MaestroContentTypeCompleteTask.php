<?php

namespace Drupal\maestro\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\maestro\Engine\MaestroEngine;


/**
 * Implements the complete task form for content type tasks when viewing.
 */
class MaestroContentTypeCompleteTask extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maestro_content_type_complete_task';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $queueID = NULL) {
    $form = [];
    
    if($queueID > 0) {
      $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
      if($task['tasktype'] == 'MaestroContentType' && MaestroEngine::canUserExecuteTask($queueID, \Drupal::currentUser()->id())) {
        if($task['tasktype'] == 'MaestroContentType') {
          $form['submit'] = array(
            '#type' => 'submit',
            '#value' => (isset($task['data']['accept_label']) && $task['data']['accept_label'] != '') ? $this->t($task['data']['accept_label']) : $this->t('Accept'),
          );
          
          //only show the reject button if it has no label
          if(isset($task['data']['reject_label']) && $task['data']['reject_label'] != '') {
            $form['reject'] = array(
              '#type' => 'submit',
              '#value' => isset($task['data']['reject_label']) ? $this->t($task['data']['reject_label']) : $this->t('Reject'),
            );
          }
          
          $form['queueid'] = array(
            '#type' => 'hidden',
            '#default_value' => $queueID,
          );
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $queueID = $form_state->getValue('queueid', 0);
    $triggeringElement = $form_state->getTriggeringElement();
    (isset($task['data']['redirect_to']) && $task['data']['redirect_to'] != '') ? $base_redirect_url = $task['data']['redirect_to'] : $base_redirect_url = '/';
    if($queueID) {
      $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
      if($task['tasktype'] == 'MaestroContentType' && MaestroEngine::canUserExecuteTask($queueID, \Drupal::currentUser()->id())) {  //just a failsafe
        if(strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
          MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
          (isset($task['data']['accept_redirect_to']) && $task['data']['accept_redirect_to'] != '') ? $redirect_url = $task['data']['accept_redirect_to'] : $redirect_url = $base_redirect_url;
        }
        else {
          //we'll complete the task, but we'll also flag it as TASK_STATUS_CANCEL
          MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
          MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
          $redirect_url = (isset($task['data']['reject_redirect_to']) && $task['data']['reject_redirect_to'] != '') ? $redirect_url = $task['data']['reject_redirect_to'] : $redirect_url = $base_redirect_url;
        }
      }
      if(isset($task['data']['supply_maestro_ids_in_url']) && $task['data']['supply_maestro_ids_in_url'] == 1) {
        $url = \Drupal\Core\Url::fromUserInput($redirect_url, ['query' => ['maestro' => 1, 'queueid' => $form_state->getValue('queueid', 0)]]);
      }
      else {
        $url = \Drupal\Core\Url::fromUserInput($redirect_url);
      }
      $form_state->setRedirectUrl($url);
    }
  }

}
