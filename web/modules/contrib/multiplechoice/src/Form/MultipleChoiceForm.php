<?php

namespace Drupal\multiplechoice\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class MultipleChoiceForm
 * @package Drupal\multiplechoice\Form
 *
 *
 */
class MultipleChoiceForm extends FormBase {
  public function getFormId() {
    // Unique ID of the form.
    return 'multiplechoice_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create a $form API array.
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#weight' => 1000,
//      '#attributes' => array(
//        'class' => array('use-ajax')
//      )
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = \Drupal::currentUser()->id();
    // Handle submitted form data.

    $input = $form_state->getUserInput();

    $entity_id = $input['question_id'];
    $revision_id = $input['question_revision_id'];
    $field_name = $input['field_name'];
    $entity_type = $input['entity_type'];
    $attempt = array();
    $delta = FALSE;
    foreach ($input as $key => $item) {
      if (substr($key, 0 , 24) == 'multiplechoice_question_') {
        $delta = intval(str_replace('multiplechoice_question_', '', $key));
        $attempt[$delta] = intval($item);
      }
    }
//    ksm($form_state->getTriggeringElement());
    if ($delta === FALSE) {
      $url = Url::fromRoute('<current>')
        ->setRouteParameters(array('question' => 1));
      $form_state->setRedirectUrl($url);

      return;
    }

    // There should only be one
    if (count($attempt) > 1) {
      drupal_set_message('An error has occurred', 'error');
    }
    $entity = entity_load($entity_type, $entity_id);
    $values = $entity->get($field_name)->getValue();

    $db = \Drupal::database();
    // After the first question is answered (or skipped) we save the result
    if ($delta == 0) {

      $db->nextId();
      // We save the user result
      $result = array(
        'nid' => $entity_id,
        'vid' => $revision_id,
        'uid' => $uid,
        'time_start' => time(),
        'score' => 0
      );
      $result_id = $db->insert('multiplechoice_quiz_node_results')
        ->fields($result)
        ->execute();
      // We need to remember the result id
      $_SESSION['multiplechoice']['result_id'] = $result_id;
    }
    else {
      $result_id =  $_SESSION['multiplechoice']['result_id'];
    }
    // We need to save the answers but we also need to record if they got stuff right or not
    // Answers get saved to
    // multiplechoice_quiz_node_results_answers: 1 line for each question
    // same nid and vid for each line
    // new field delta for the delta value of the question
    // field name in case more than one field per node


    $skipped = isset($attempt[$delta]) ? 0 : 1;
    $correct = $skipped == 0 && $attempt[$delta] == intval($values[$delta]['correct_answer']) ? 1 : 0;
    $points = $correct ? $values[$delta]['difficulty'] : 0;
    $answers = array(
      'result_id' => $result_id,
      'question_nid' => $entity_id,
      'question_vid' => $revision_id,
      'is_correct' => $correct,
      'is_skipped' => $skipped,
      'points_awarded' => $points,
      'answer_timestamp' => time(),
      'number' => 0,
      'is_doubtful' => 0,
      'delta' => $delta,
      'field_name' => $field_name
    );

    $db->insert('multiplechoice_quiz_node_results_answers')
      ->fields($answers)
      ->execute();
      // Calculate score

    // Get score
    $query = $db->select('multiplechoice_quiz_node_results', 'qnr');
    $query->addField('qnr', 'score');
    $query->condition('qnr.result_id', $result_id);
    $score = $query
      ->execute()
      ->fetchField();
    $score = $score + $points;

    $fields = array(
      'score' => $score,
      'time_end' => time()
    );
    // Insert score
    $db->update('multiplechoice_quiz_node_results')
      ->fields($fields)
      ->condition('result_id', $result_id)
      ->execute();
    $route = \Drupal::service('current_route_match')->getRouteName();
    $current_path = \Drupal::service('path.current')->getPath();
//    $current_url = Url::fromRoute('<current>');
////
////    // Redirection
////    $route = \Drupal::routeMatch()->getRouteName();
//    dpm($current_url);

    $num_items = count($values);
    // When we have finished we need to redirect elsewhere
    if ($delta >= ($num_items - 1)) {
      drupal_set_message('Well done you have finished');
      $url = Url::fromRoute('<front>');
      $form_state->setRedirectUrl($url);
    }
    else {
      $url = Url::fromRoute('<current>')
        ->setRouteParameters(array('question' => ($delta + 2)));
      $form_state->setRedirectUrl($url);
    }

  }

  protected function submitMultichoiceItem(&$form, &$form_submit) {

  }


}
