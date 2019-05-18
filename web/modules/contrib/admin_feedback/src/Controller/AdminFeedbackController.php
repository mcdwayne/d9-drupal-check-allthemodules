<?php
/**
 * @file
 * Contains \Drupal\admin_feedback\Controller\AdminFeedbackController.
 */

namespace Drupal\admin_feedback\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the admin_feedback module.
 */
class AdminFeedbackController extends ControllerBase {

  /**
   * Access function.
   */
  public function accessAdmin(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('access administration menu'));
  }


  /**
   * Function for gettings single nodes score.
   */
  public function getCurrentNodeScore($node_id = NULL) {
    if ($node_id != NULL) {
      $connection = \Drupal::database();
      $connection = $connection->select('admin_feedback_score', 'f')
        ->fields('f', ['id', 'count', 'yes_count', 'no_count', 'total_score'])
        ->condition('nid', $node_id)
        ->execute()
        ->fetchAll();
      return $connection;
    }
  }


  /**
   * Function for inserting rows to database.
   */
  public function insertFeedback($feedback = NULL, $node = NULL) {
    $user = \Drupal::currentUser();
    $query_method = 'insert';
    $record = [
      'nid' => $node,
      'created' => time(),
      'feedback_type' => $feedback,
      'feedback_message' => NULL,
    ];
    $query = \Drupal::database()->$query_method('admin_feedback')->fields($record);
    if ($query_method == 'upsert') {
      $query->key('id');
    }
    $last_inserted_id = $query->execute();
    $encoded64_id = base64_encode($last_inserted_id);

    // Invalidate cache.
    \Drupal\Core\Cache\Cache::invalidateTags(['feedback_cache_tags']);

    return $encoded64_id;
  }

  /**
   * Function for updating rows in database.
   */
  public function updateFeedback($feedback_id = NULL, $feedback_message = NULL) {
    if (!empty($feedback_id)) {
      $feedback_id = base64_decode($feedback_id);
    }

    if ($feedback_message != NULL && !empty($feedback_message)) {
      $connection = \Drupal::database();
      $connection->update('admin_feedback')
        ->fields([
          'feedback_message' => $feedback_message
        ])
        ->condition('id', $feedback_id)
        ->execute();
    }
  }


  /**
   * Function for inserting score.
   */
  public function insertScore($node_id, $feedback, $count) {
    $count = $count + 1;
    ($feedback == 1) ? $yes = 1 : $no = 1;
    ($feedback == 1) ? $total_score = 100 : $total_score = 0;
    $query_method = 'insert';
    $record = [
      'nid' => $node_id,
      'count' => $count,
      'yes_count' => $yes,
      'no_count' => $no,
      'total_score' => $total_score,
    ];
    $query = \Drupal::database()->$query_method('admin_feedback_score')->fields($record);
    $query->execute();
  }

  /**
   * Function for updating score.
   */
  public function updateScore($node_id, $feedback, $count, $total_score, $yes_count, $no_count) {
    if ($node_id != NULL && !empty($node_id)) {
      ($feedback == 1) ? $yes_count = $yes_count + 1 : $no_count = $no_count + 1;
      $count = $count + 1;
      $total_score = round($yes_count / $count * 100);
      $connection = \Drupal::database();
      $connection->update('admin_feedback_score')
        ->fields([
          'count' => $count,
          'yes_count' => $yes_count,
          'no_count' => $no_count,
          'total_score' => $total_score,
        ])
        ->condition('nid', $node_id)
        ->execute();
    }
  }


  /**
   * Function for receiving votes.
   */
  public function adminFeedbackVoteReceiver() {
    $feedback = \Drupal::request()->request->get('vote');
    $node_id = \Drupal::request()->request->get('node_id');

    switch ($feedback) {
      case 'yes':
        $feedback = ADMIN_FEEDBACK_YES_VALUE;
        break;

      case 'no':
        $feedback = ADMIN_FEEDBACK_NO_VALUE;
        break;

      default:
        $feedback = NULL;
        break;
    }
    $last_inserted_vote_id[] = $this->insertFeedback($feedback, $node_id);

    $node_score = $this->getCurrentNodeScore($node_id);
    if (!$node_score) {
      $count = 0;
      $this->insertScore($node_id, $feedback, $count);
    }
    else {
      $count = $node_score[0]->count;
      $total_score = $node_score[0]->total_score;
      $yes_count = $node_score[0]->yes_count;
      $no_count = $node_score[0]->no_count;
      $this->updateScore($node_id, $feedback, $count, $total_score, $yes_count, $no_count);
    }

    return new JsonResponse($last_inserted_vote_id, 200, ['Content-Type' => 'application/json']);
  }

  /**
   * Function for marking inspections.
   */
  public function markInspected() {
    $response = new AjaxResponse();
    $feedback_id = \Drupal::request()->request->get('feedback_id');

    if ($feedback_id != NULL && !empty($feedback_id)) {
      $connection = \Drupal::database();
      $connection->update('admin_feedback')
        ->fields([
          'inspected' => 1
        ])
        ->condition('id', $feedback_id)
        ->execute();
    }

    // Invalidate cache.
    \Drupal\Core\Cache\Cache::invalidateTags(['feedback_cache_tags']);

    return $response;
  }

  /**
   * Function for unmarking inspections.
   */
  public function markUnInspected() {
    $response = new AjaxResponse();
    $feedback_id = \Drupal::request()->request->get('feedback_id');

    if ($feedback_id != NULL && !empty($feedback_id)) {
      $connection = \Drupal::database();
      $connection->update('admin_feedback')
        ->fields([
          'inspected' => 0
        ])
        ->condition('id', $feedback_id)
        ->execute();
    }

    // Invalidate cache.
    \Drupal\Core\Cache\Cache::invalidateTags(['feedback_cache_tags']);

    return $response;
  }

  /**
   * Function for exporting data from the database.
   */
  public function exportDbFeedback() {
    $response = new Response();

    $connection = \Drupal::database();
    $query = $connection->select('admin_feedback', 'f');
    $query->innerJoin('node_field_data', 'd', 'd.nid = f.nid');
    $query->fields('f', ['id', 'nid', 'created', 'feedback_type', 'feedback_message', 'inspected']);
    $query = $query->execute();
    $results = $query->fetchAll(\PDO::FETCH_OBJ);

    foreach ($results as $result ) {
      if ($result->feedback_type ==  1) {
        $result->feedback_type = 'positive';
      }
      elseif ($result->feedback_type == 0) {
        $result->feedback_type = 'negative';
      }
      if ($result->inspected == 1) {
        $result->inspected = 'Yes';
      }
      $result->created = date('d-m-Y', $result->created);
    }

    $output = "\xEF\xBB\xBF";
    $output .= 'Nr, URL, Created, Feedback, Message, Inspected' . "\n";

    foreach ($results as $row) {
      $output .=
        $row->id . ',' .
        \Drupal::request()->getSchemeAndHttpHost().'/node/'. $row->nid . ',' .
        $row->created . ',' .
        $row->feedback_type . ',' .
        $row->feedback_message . ',' .
        $row->inspected;
      $output .= "\n";
    }

    $response->setContent($output);
    $response->headers->set("Content-Type", "text/csv; charset=UTF-8");
    $response->headers->set("Content-Disposition", "attachment; filename=feedback_data.csv");

    return $response;
  }

}
