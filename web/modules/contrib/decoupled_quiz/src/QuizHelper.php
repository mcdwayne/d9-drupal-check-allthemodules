<?php

namespace Drupal\decoupled_quiz;

use Drupal\decoupled_quiz\Entity\Quiz;
use Drupal\decoupled_quiz\Entity\Result;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
 * Class QuizHelper.
 *
 * @package Drupal\decoupled_quiz
 */
class QuizHelper extends ControllerBase {

  protected $quiz;

  protected $qid;

  /**
   * {@inheritdoc}
   */
  public function getQuiz() {
    return $this->quiz;
  }

  /**
   * QuizHelper constructor.
   *
   * @param int $qid
   *   Quiz ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct($qid) {
    $this->qid = $qid;
    $this->quiz = $this->entityTypeManager()->getStorage(DECOUPLED_QUIZ_QUIZ_ENTITY)->load($qid);
  }

  /**
   * Returns basic Quiz fields.
   *
   * @return array
   *   Array with quiz data.
   */
  public function getQuizFields() {
    $result['intro_title'] = $this->quiz->get('name')->getString();
    $result['intro_description'] = $this->quiz->get('field_quiz_intro_description')->getString();
    $result['intro_button_text'] = $this->quiz->get('field_quiz_intro_button_text')->getString();
    $result['intro_image'] = $this->quiz->get('field_quiz_intro_image')->getString();
    $result['result_title'] = $this->quiz->get('field_quiz_result_title')->getString();
    $result['result_button_text'] = $this->quiz->get('field_quiz_result_button_text')->getString();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntities($entity, $parentType, $targetType) {
    $results = [];
    if ($entity === NULL) {
      $entity = $this->quiz;
    }

    $bundle = $entity->get('type')->getString();
    $fields = $this->entityManager()->getFieldDefinitions($parentType, $bundle);
    foreach ($fields as $name => $field) {
      $field_type = $entity->get($name)->getFieldDefinition()->getType();
      if ($field_type === 'entity_reference') {
        $target = $entity->get($name)->getSettings()['target_type'];
        if ($target === $targetType) {
          $results = array_merge($entity->get($name)->referencedEntities(), $results);
        }
      }
    }

    return $results;
  }

  /**
   * Returns questions assotiated with quiz.
   */
  public function getFullQuizData() {
    $result = [];
    $questions = $this->getReferencedEntities($this->quiz, DECOUPLED_QUIZ_QUIZ_ENTITY, DECOUPLED_QUIZ_QUESTION_ENTITY);

    foreach ($questions as $delta => $question) {
      $questionId = (int) $question->get('id')->getString();
      $result['questions'][] = [
        'id' => $questionId,
        'title' => $question->get('name')->getString(),
        'order' => $delta,
      ];
      $answers = $this->getReferencedEntities($question, DECOUPLED_QUIZ_QUESTION_ENTITY, DECOUPLED_QUIZ_ANSWER_ENTITY);

      foreach ($answers as $answer) {
        $answerId = $answer->get('id')->getString();
        $answerText = $answer->get('name')->getString();
        $answerExist = 0;
        if (isset($result['answers'])) {
          foreach ($result['answers'] as &$existingAnswer) {
            if ($existingAnswer['id'] == $answerId) {
              $answerExist = 1;
              $existingAnswer['questions'][] = $questionId;
            }
          }
        }

        if (!$answerExist) {
          $result['answers'][] = [
            'id' => (int) $answerId,
            'text' => $answerText,
            'questions' => [$questionId],
          ];
        }
      }
    }

    return $result;
  }

  /**
   * Returns questions assotiated with quiz in simple manner.
   */
  public function getMinQuizData() {
    $questions = $this->getReferencedEntities($this->quiz, DECOUPLED_QUIZ_QUIZ_ENTITY, DECOUPLED_QUIZ_QUESTION_ENTITY);
    $options = [];
    foreach ($questions as $question) {
      $questionId = $question->get('id')->getString();
      $options[$questionId]['question_name'] = $question->get('name')->getString();
      $answers = $this->getReferencedEntities($question, DECOUPLED_QUIZ_QUESTION_ENTITY, DECOUPLED_QUIZ_ANSWER_ENTITY);
      foreach ($answers as $answer) {
        $options[$questionId]['answers'][$answer->get('id')->getString()] = $answer->get('name')->getString();
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlows() {
    $connection = Database::getConnection();
    $flows = $connection->select('decoupled_quiz_flow', 'cf')
      ->fields('cf', ['flow'])
      ->condition('qid', $this->qid)
      ->execute()
      ->fetchField();
    return unserialize($flows);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlowsMinified() {
    $flowData = [];
    $resultPages = [];
    $flows = $this->getFlows();
    foreach ($flows as $fid => $flow) {
      $data = [];
      foreach ($flow['questions'] as $id => $question) {
        $data[$id] = array_keys(array_filter($question['answers']));
      }
      $resultId = reset($flow['result_page'])['target_id'];
      $resultPages[] = $resultId;
      $flowData['flows'][] = [
        'id' => (int) $fid,
        'result' => (int) $resultId,
        'data' => $data,
      ];
    }

    $flowData['results'] = $this->getResultPages($resultPages);

    return $flowData;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultPages(array $resultPagesIds) {
    $results = [];
    $addedResultPages = [];
    foreach ($resultPagesIds as $resultPagesId) {
      $resultEntity = Result::load($resultPagesId);
      $resultId = (int) $resultEntity->get('id')->getString();
      if (!in_array($resultId, $addedResultPages)) {
        $result['id'] = $resultId;
        $result['title'] = $resultEntity->name->value;
        $result['link'] = $resultEntity->field_result_link->value;
        $result['description'] = $resultEntity->field_result_description->value;
        $result['img'] = $resultEntity->field_result_image->value;

        $results[] = $result;
        $addedResultPages[] = $resultId;
      }
    }

    return $results;
  }

}
