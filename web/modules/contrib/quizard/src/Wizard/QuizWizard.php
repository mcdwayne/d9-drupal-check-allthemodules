<?php

/**
 * @file
 * Contains \Drupal\quizard\Wizard\QuizWizard.
 */

namespace Drupal\quizard\Wizard;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\quizard\Entity\QuizResults;
use Drupal\quizard\QuizardEvent;

class QuizWizard extends FormWizardBase {
  protected $quiz;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Drupal\user\SharedTempStoreFactory $tempstore, \Drupal\Core\Form\FormBuilderInterface $builder, \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver, \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher, \Drupal\Core\Routing\RouteMatchInterface $route_match, $tempstore_id, $machine_name, $step = NULL) {
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $route_match, $tempstore_id, $machine_name, $step);

    $this->quiz = $route_match->getParameter('quiz');
    $this->quiz_config = $this->config('quizard.config');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'quizard.step';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Quiz Wizard Label');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Quiz Wizard');
  }

  /**
   * Build operations/steps based on paragraphs from the Quiz content type.
   *
   * @return array
   *  Quiz operations.
   */
  public function operationsFromQuizParagraphs() {
    /* @var $paragraph_entity_reference \Drupal\Core\Field\EntityReferenceFieldItemList */
    $paragraph_entity_reference = $this->quiz->get('field_quiz_paragraph');
    $title = $this->quiz->getTitle();
    $steps = array();
    // Create form steps based on paragraphs.
    foreach ($paragraph_entity_reference->referencedEntities() as $step_key => $paragraph) {
      /* @var $paragraph \Drupal\paragraphs\Entity\paragraph */
      $field_values = array();
      $paragraph_type = $paragraph->getType();
      // Load field values.
      foreach ($paragraph->getFieldDefinitions() as $field_definition) {
        // User defined fields.
        if ($field_definition instanceof FieldConfig) {
          $field_name = $field_definition->getName();
          if ($field_name != 'field_quiz_video') {
            $field_values[$field_name] = $paragraph->get($field_name)
              ->getValue();
          }
          else {
            $field_values[$field_name] = $paragraph->get($field_name);
          }
        }
      }

      $steps['step' . '-' . $step_key] = array(
        // Form class matches paragraph type machine name.
        'form' => 'Drupal\quizard\Form\\' . $paragraph_type,
        'title' => $title,
        'values' => array($this->step => $field_values),
      );
    }

    return $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    return $this->operationsFromQuizParagraphs();
  }

  /**
   * Helper function to grade and save the quiz.
   *
   * @param $cached_values
   * @return int
   *   Boolean Pass/Fail.
   */
  public function processResults($cached_values) {
    $result = 0; // Fail.
    $results = array();
    $answer_keys = array(
      'field_quiz_true_false_answer',
      'field_quiz_multi_choice_answer'
    );
    // Grade quiz.
    foreach ($cached_values['answers'] as $step => $answer) {
      $answer_key = array_keys(array_intersect_key(array_flip($answer_keys), $cached_values[$step]));
      $correct_answer = $cached_values[$step][reset($answer_key)][0]['value'];
      $results[$step] = $answer == $correct_answer ? 1 : 0;
    }
    $number_correct = isset(array_count_values($results)[1]) ? array_count_values($results)[1] : 0;
    // Average > pass/fail level.
    if (!empty($number_correct) && ($number_correct / count($results)) >= $this->quiz_config->get('pass_level')) {
      $result = 1; // Pass.
    }

    // Save pass/fail.
    $quiz_results = QuizResults::create(array(
      'type' => 'quiz_results',
      'uid' => \Drupal::currentUser()->id(),
      'name' => $this->quiz->getTitle(),
      'field_quiz_results_quiz' => array(
        array(
          'target_id' => $this->quiz->id(),
          'target_revision_id' => $this->quiz->getRevisionId(),
        ),
      ),
      'field_quiz_results_result' => array(
        'value' => $result,
      ),
    ));

    // Event.
    $quiz_event = $this->dispatcher->dispatch(
      'quiz_completed',
      new QuizardEvent(
        array(
          'quiz' => $this->quiz,
          'quiz_results' => $quiz_results,
        )
      )
    );
    $quiz_results = $quiz_event->getEvent()['quiz_results'];

    $quiz_results->save();

    return $result;

  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    // Grade the quiz and save it.
    $cached_values['grade'] = $this->processResults($cached_values);
    // Display results.
    $form_state->setRedirect('quizard.results');
    $tempstore = \Drupal::service('user.private_tempstore')->get('quizard');
    $tempstore->set('results', $cached_values);

    parent::finish($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function getNextParameters($cached_values) {
    // Get the steps by key.
    $operations = $this->getOperations($cached_values);
    $steps = array_keys($operations);
    // Get the steps after the current step.
    $after = array_slice($operations, array_search($this->getStep($cached_values), $steps) + 1);
    // Get the steps after the current step by key.
    $after_keys = array_keys($after);
    $step = reset($after_keys);
    if (!$step) {
      $keys = array_keys($operations);
      $step = end($keys);
    }
    // Include quiz ID.
    return [
      'machine_name' => $this->getMachineName(),
      'quiz' => $this->quiz->id(),
      'step' => $step,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousParameters($cached_values) {
    $operations = $this->getOperations($cached_values);
    $step = $this->getStep($cached_values);

    // Get the steps by key.
    $steps = array_keys($operations);
    // Get the steps before the current step.
    $before = array_slice($operations, 0, array_search($step, $steps));
    // Get the steps before the current step by key.
    $before = array_keys($before);
    // Reverse the steps for easy access to the next step.
    $before_steps = array_reverse($before);
    $step = reset($before_steps);
    // Include quiz ID.
    return [
      'machine_name' => $this->getMachineName(),
      'quiz' => $this->quiz->id(),
      'step' => $step,
    ];
  }

  /**
   * Progress bar.
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $operations = $this->getOperations($cached_values);
    $step = $this->getStep($cached_values);
    $time_to_complete = $this->quiz->get('field_quiz_time')->getValue()[0]['value'];

    // Progress bar.
    $prefix = [
      '#theme' => ['quizard_progress_bar'],
      '#wizard' => $this,
      '#cached_values' => $cached_values,
      '#operations' => $operations,
      '#step' => $step,
      '#time' => $time_to_complete,
      '#attached' => [
        'library' => ['quizard/results'],
      ],
    ];
    $form['#prefix'] = \Drupal::service('renderer')->render($prefix);
    return $form;
  }

}