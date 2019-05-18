<?php

/**
 * @file
 * Contains \Drupal\quizard\Controller\Results.
 */

namespace Drupal\quizard\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuizWizardAccess extends ControllerBase{

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entity_query = $entity_query;
    $this->quiz_config = $this->config('quizard.config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * Custom access check for quiz wizard.
   *
   * @return AccessResult
   *   True if user has not taken this quiz yet.
   *   False and sets a message if the user has.
   */
  public function access(AccountInterface $account, RouteMatch $route_match) {
    return AccessResult::allowedIf($this->userHasntTakenQuiz($account->id(), $route_match));
  }

  /**
   * Helper function to determine if the user has taken the quiz or has retries
   * left.
   *
   * @return bool
   */
  public function userHasntTakenQuiz($uid, $route_match) {
    $quiz_id = $route_match->getParameter('quiz')->id();
    $taken_quiz = $this->entity_query->get('quiz_results')
      ->condition('user_id', $uid, '=')
      ->condition('field_quiz_results_quiz', $quiz_id, '=')
      ->count()
      ->execute()
    ;
    if ($taken_quiz < $this->quiz_config->get('retries')) {
      return TRUE;
    }
    else {
      drupal_set_message('Sorry, you\'ve already taken this quiz.', 'status');
      return FALSE;
    }
  }
}
