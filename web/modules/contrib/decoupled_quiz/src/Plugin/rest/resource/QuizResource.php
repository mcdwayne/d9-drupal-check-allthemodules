<?php

namespace Drupal\decoupled_quiz\Plugin\rest\resource;

use Drupal\decoupled_quiz\QuizHelper;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a Quiz Resource.
 *
 * @RestResource(
 *   id = "quiz_resource",
 *   label = @Translation("Quiz Resource"),
 *   uri_paths = {
 *     "canonical" = "/v1/quizzes/{quiz_id}"
 *   }
 * )
 */
class QuizResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Returns quizzes.
   */
  public function get($quiz_id) {
    $data = $this->getQuiz($quiz_id);
    $build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PATCH, DELETE',
    ];

    $response = new ResourceResponse($data, 200, $headers);

    return $response->addCacheableDependency($build);
  }

  /**
   * Returns complete response for quiz.
   *
   * @param int $quiz_id
   *   Quiz ID.
   *
   * @return array
   *   Completed array for JSON.
   */
  private function getQuiz($quiz_id) {
    $response = [];

    // @TODO get multiple quizzes.
    $quiz = new QuizHelper($quiz_id);
    $questionsData = $quiz->getFullQuizData();
    $quizData = $quiz->getQuizFields();
    $flowsData = $quiz->getFlowsMinified();

    $response['quiz'] = [
      'id' => (int) $quiz_id,
      'intro' => [
        'title' => $quizData['intro_title'],
        'description' => $quizData['intro_description'],
        'button' => $quizData['intro_button_text'],
        'img' => $quizData['intro_image'],
      ],
      'result' => [
        'title' => $quizData['result_title'],
        'button' => $quizData['result_button_text'],
      ],
    ];
    $response['questions'] = $questionsData['questions'];
    $response['answers'] = $questionsData['answers'];
    $response['flows'] = $flowsData['flows'];
    $response['results'] = $flowsData['results'];

    return $response;
  }

}
