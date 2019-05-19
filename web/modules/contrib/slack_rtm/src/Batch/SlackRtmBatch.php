<?php

namespace Drupal\slack_rtm\Batch;

use Drupal\slack_rtm\Entity\SlackRtmMessageCreate;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Batch Class for Slack RTM.
 *
 * @ingroup slack_rtm
 */
class SlackRtmBatch {

  /**
   * Common batch processing callback for all operations.
   *
   * Required to load our include the proper batch file.
   *
   * @param array $batch
   *   The batch array containing all the datas.
   * @param object &$context
   *   The batch context object.
   */
  public static function batchProcess(array $batch,  &$context) {

    if (isset($batch['channels_list'])) {
      $channels_list = $batch['channels_list'];
      $channel = $channels_list[$batch['id']];
    }
    else {
      $channel = $batch['id'];
    }

    // Show message.
    $msg = t('Now assimilating messages from channel %channel',
      ['%channel' => $channel]
    );
    $context['message'] = '<h2>' . $msg . '</h2>';

    // Generate the entity.
    $result = (new SlackRtmMessageCreate($batch))->generateEntity();

    // Set the result.
    if ($result !== NULL) {
      $context['results'][] = $result;
    }

  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One message assimilated.', '@count messages assimilated.');
      drupal_set_message($message, 'status', TRUE);
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))), 'status', TRUE);
    }

    // Redirect to Slack RTM page.
    $response = new RedirectResponse('/admin/structure/slack_rtm_message');
    $response->send();
  }
}