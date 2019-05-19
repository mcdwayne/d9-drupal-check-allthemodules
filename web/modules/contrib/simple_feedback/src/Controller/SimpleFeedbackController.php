<?php /**
 * @file
 * Contains \Drupal\simple_feedback\Controller\SimpleFeedbackController.
 */

namespace Drupal\simple_feedback\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the simple_feedback module.
 */
class SimpleFeedbackController extends ControllerBase {

  /**
   * An AJAX callback to record the feedback and return a response message.
   *
   * @param int $node
   *   A node ID representing the content with feeback.
   * @param string $feedback
   *   A string expected to be either "yes" or "no", which is then converted
   *   to +1 or -1 before being recorded in the database.
   *
   * @return array
   *   An AJAX delivery array with a command to insert a message.
   */
  public function SimpleFeedbackAjaxCallback($node, $feedback) {
    $user = \Drupal::currentUser();
    $message = NULL;
    $response = new AjaxResponse();

    switch ($feedback) {
      case 'yes':
        $feedback = SIMPLE_FEEDBACK_YES_VALUE;
        $message = $this->t('Thank you for your feedback.');
        break;

      case 'no':
        $feedback = SIMPLE_FEEDBACK_NO_VALUE;
        $message = $this->t('Thank you for your feedback.');
        break;

      default:
        $feedback = 0;
    }

    if (!empty($message)) {
      $response->addCommand(new ReplaceCommand('#feedback-message', $message));

      $query_method = 'insert';
      $record = [
        'nid' => $node,
        'uid' => $user->id(),
        'created' => time(),
        'source' => \Drupal::request()->getClientIp(),
        'value' => $feedback,
      ];

      // Check for existing vote.
      $query = \Drupal::database()->select('simple_feedback', 'a');
      $query->condition('a.nid', $record['nid']);
      $query->condition('a.source', $record['source']);
      $query->fields('a');
      $results = $query->execute()->fetchAll();
      if (count($results)) {
        if ($results[0]->value == $record['value']) {
          // Do not write a record if this is a duplicate vote.
          return $response;
        }
        else {
          $query_method = 'upsert';
          $record['id'] = $results[0]->id;
        }
      }

      $query = \Drupal::database()->$query_method('simple_feedback')->fields($record);
      if ($query_method == 'upsert') $query->key('id');
      $query->execute();
    }

    return $response;
  }

  /**
   * Counts the number of votes for a node and returns the sum.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return array
   *   A JSON array containing the number of times
   *   a vote has been cast up or down.
   */
  public function SimpleFeedbackGetVotesCallback($nid) {
    $yes_count = 0;
    $no_count = 0;

    $query = \Drupal::database()->select('simple_feedback', 'a');
    $query->condition('a.nid', $nid);
    $query->fields('a');
    $results = $query->execute()->fetchAll();

    if (count($results)) {
      foreach ($results as $result) {
        switch ($result->value) {
          case SIMPLE_FEEDBACK_YES_VALUE:
            $yes_count++;
            break;

          case SIMPLE_FEEDBACK_NO_VALUE:
            $no_count++;
            break;
        }
      }
    }

    $content = [
      'count' => [
        'yes' => $yes_count,
        'no' => $no_count,
      ],
    ];

    $response = new Response();
    $response->setContent(json_encode($content));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }
}
