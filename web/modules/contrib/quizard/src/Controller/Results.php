<?php

/**
 * @file
 * Contains \Drupal\quizard\Controller\Results.
 */

namespace Drupal\quizard\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Results extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('quizard');
    $this->config = $this->config('quizard.config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('user.private_tempstore'));
  }

  /**
   * Helper function to convert 0 and 1's to "True" and "False" when displaying
   * results to the user.
   *
   * @return array
   *   True and false strings.
   */
  public function convertTrueFalse($question_key, $answer, $correct_answer) {
    if ($question_key[0] == 'field_quiz_true_false_quest') {
      $answer = ($answer) ? 'True' : 'False';
      $correct_answer = ($correct_answer) ? 'True' : 'False';
    }

    return [$answer, $correct_answer];
  }

  /**
   * Results page controller. Displays results of the last completed quiz.
   *
   * @return array
   *   Render array.
   */
  public function results() {
    $results = [];
    $cached_values = $this->tempStore->get('results');
    $answer_keys = ['field_quiz_true_false_answer', 'field_quiz_multi_choice_answer'];
    $question_keys = ['field_quiz_true_false_quest', 'field_quiz_multi_choice_quest'];
    // Create render array.
    $grade = ($cached_values['grade']) ? $this->config->get('success_message') : $this->config->get('failure_message');
    foreach ($cached_values['answers'] as $step => $answer) {
      $answer_key = array_keys(array_intersect_key(array_flip($answer_keys), $cached_values[$step]));
      $question_key = array_keys(array_intersect_key(array_flip($question_keys), $cached_values[$step]));
      list($answer, $correct_answer) = $this->convertTrueFalse($question_key, $answer, $cached_values[$step][reset($answer_key)][0]['value']);
      $results[] = array(
        '#type' => 'details',
        '#title' => html_entity_decode(strip_tags($cached_values[$step][$question_key[0]][0]['value'])),
        '#attributes' => array(
          'class' => array('question', $correct_answer == $answer ? 'correct' : 'incorrect'),
        ),
        'answer' => array(
          '#type' => 'item',
          '#markup' => 'Your answer: ' . $answer,
        ),
        'correct_answer' => array(
          '#type' => 'item',
          '#markup' => 'Correct answer: ' . $correct_answer,
        ),
      );
    }

    return array(
      '#theme' => 'quizard_results',
      '#grade' => $grade,
      '#results' => $results,
      '#attached' => array(
        'library' => array('quizard/results'),
      ),
    );
  }

}
