<?php
/**
 * @file
 * Contains \Drupal\h5p_analytics\Plugin\QueueWorker\BatchQueue.
 */
namespace Drupal\h5p_analytics\Plugin\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\h5p_analytics\Exception\MissingConfigurationException;
/**
 * Processes statement batches.
 *
 * @QueueWorker(
 *   id = "h5p_analytics_batches",
 *   title = @Translation("Statement batch processing worker"),
 *   cron = {"time" = 1200}
 * )
 */
class BatchQueue extends QueueWorkerBase {

  /**
   * Adds data to statement log. Mostly happens if request is successful or
   * @param int $code      Status code
   * @param string $reason Response reason
   * @param int $count     Number of statements in the batch
   * @param string $data   JSON-encoded data or NULL
   */
  private function addToStatementLog($code, $reason, $count, $data) {
    \Drupal::service('database')->insert('h5p_analytics_statement_log')
    ->fields([
      'code' => $code,
      'reason' => $reason,
      'count' => $count,
      'data' => $data,
      'created' => REQUEST_TIME,
    ])
    ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    try {
      $response = \Drupal::service('h5p_analytics.lrs')->sendToLrs($data);
      // TODO This could throw an error, needs to be handled
      $this->addToStatementLog($response->getStatusCode(), $response->getReasonPhrase(), sizeof($data), NULL);
    } catch (MissingConfigurationException $e) {
      throw new SuspendQueueException($e->getMessage());
    } catch (RequestException $e) {
      switch((int)$e->getCode()) {
        case 400:
          // TODO This could throw an error, needs to be handled
          $this->addToStatementLog($e->getCode(), $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '', sizeof($data), json_encode($data));
        break;
        case 401:
          throw new SuspendQueueException($e->getMessage());
        break;
        case 403:
        case 404:
        case 500:
        case 502:
        case 503:
          // These cases will allow data transfer to be retried
          throw $e;
        break;
        default:
          // TODO See if we could detect timeout case and make the try again logic the default one instead
          // The only concern is tha case of request timing out and data potentially beind accepted by the server
          $this->addToStatementLog($e->getCode(), $e->hasResponse() ? $e->getResponse()->getReasonPhrase() : '', sizeof($data), json_encode($data));
      }
    } catch (\Exception $e) {
      throw $e;
    }
  }
}
