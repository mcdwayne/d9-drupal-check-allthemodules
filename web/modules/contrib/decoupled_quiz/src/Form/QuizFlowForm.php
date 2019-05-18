<?php

namespace Drupal\decoupled_quiz\Form;

use Drupal\decoupled_quiz\Entity\Result;
use Drupal\decoupled_quiz\QuizHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class QuizFlowForm.
 *
 * @package Drupal\decoupled_quiz\Form
 */
class QuizFlowForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_flow_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $quiz_id = \Drupal::routeMatch()->getParameters()->get('quiz');
    $quizHelper = new QuizHelper($quiz_id);
    $options = $quizHelper->getMinQuizData();

    $flows = [];
    if (!$form_state->getTriggeringElement()) {
      $flows = $quizHelper->getFlows();
      $form_state->set('flows', $flows);
    }
    if (!$flows) {
      $flows = $form_state->get('flows');
    }

    $quizName = $quizHelper->getQuiz()->get('name')->getString();
    $form['#tree'] = TRUE;
    $form['flows_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flows for quiz: @name (@id)', ['@name' => $quizName, '@id' => $quiz_id]),
      '#prefix' => '<div id="flows-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    if (empty($flows)) {
      $form_state->set('flows', [0]);
      $flows = $form_state->get('flows');
    }

    foreach ($flows as $i => $flow) {
      $form['flows_fieldset']['flow'][$i] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => 'Flow #' . $i,
      ];
      $form['flows_fieldset']['flow'][$i]['questions'] = [
        '#type' => 'table',
        '#title' => 'Flow',
        '#header' => ['Question', 'Answers'],
      ];

      foreach ($options as $qid => $question) {
        $form['flows_fieldset']['flow'][$i]['questions'][$qid]['question'] = [
          '#type' => 'label',
          '#title' => $question['question_name'],
        ];
        $form['flows_fieldset']['flow'][$i]['questions'][$qid]['answers'] = [
          '#type' => 'checkboxes',
          '#options' => $question['answers'],
          '#default_value' => $flows[$i]['questions'][$qid]['answers'],
        ];
      }

      $result = NULL;
      if (isset($flows[$i]['result_page'])) {
        $result = reset($flows[$i]['result_page']);
        $result = Result::load($result['target_id']);
      }

      $resultPage = isset($flows[$i]['result_page']);
      $form['flows_fieldset']['flow'][$i]['result_page'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Result page'),
        '#target_type' => 'result',
        '#tags' => TRUE,
        '#default_value' => $result,
      ];

      if (count($flows) > 1) {
        $form['flows_fieldset']['flow'][$i]['remove'] = [
          '#type' => 'submit',
          '#value' => t('Remove flow'),
          '#submit' => ['::removeFlow'],
          '#ajax' => [
            'callback' => '::addMoreFlowCallback',
            'wrapper' => 'flows-fieldset-wrapper',
          ],
          '#name' => 'remove_flow_' . $i,
          '#attributes' => [
            'class' => ['button--danger'],
          ],
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['add_flow'] = [
      '#type' => 'submit',
      '#value' => t('Add Another Flow'),
      '#submit' => ['::addFlow'],
      '#ajax' => [
        'callback' => '::addMoreFlowCallback',
        'wrapper' => 'flows-fieldset-wrapper',
      ],
      '#weight' => 10,
    ];

    $form_state->setCached(FALSE);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Flows'),
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addFlow(array &$form, FormStateInterface $form_state) {
    $flows = $form_state->get('flows');
    $flows[] = count($flows) > 0 ? max(array_keys($flows)) + 1 : 0;
    $form_state->set('flows', $flows);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreFlowCallback(array &$form, FormStateInterface $form_state) {
    $flows = $form_state->get('flows');
    return $form['flows_fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeFlow(array &$form, FormStateInterface $form_state) {
    $flows = $form_state->get('flows');
    $flow_id = str_replace('remove_flow_', '', $form_state->getTriggeringElement()['#name']);
    unset($flows[$flow_id]);
    $form_state->set('flows', $flows);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $flows = $form_state->getValue(['flows_fieldset', 'flow']);
    foreach ($flows as $fid => $flow) {
      unset($flows[$fid]['remove']);
    }
    $qid = \Drupal::routeMatch()->getParameter('quiz');
    $connection = Database::getConnection();
    $connection->merge('decoupled_quiz_flow')
      ->key(['qid' => $qid])
      ->fields([
        'flow' => serialize($flows),
      ])
      ->execute();
    $output = t('Flows for Quiz ID: @qid were saved', ['@qid' => $qid]);
    drupal_set_message($output);
  }

}
