<?php

namespace Drupal\onehub\Batch;

use Drupal\onehub\OneHubApi;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Batch Class for OneHub.
 *
 * @ingroup onehub
 */
class OneHubBatch {

  /**
   * Common batch processing callback for all operations.
   *
   * Required to load our include the proper batch file.
   *
   * @param array $item
   *   The item we are processing
   * @param object &$context
   *   The batch context object.
   */
  public static function batchProcess(array $item,  &$context) {

    // Show message.
    $msg = t('Now checking %folder',
      ['%folder' => $item['filename']]
    );
    $context['message'] = '<h2>' . $msg . '</h2>';

    $result = self::processItem($item);

    if ($result !== NULL) {
      $context['results'][] = $result;
    }
  }

  /**
   * Function for handling the processing of each item.
   *
   * @param array $item
   *   The item we are processing
   */
  public static function processItem($item) {

    // Only process files.
    if ($item['pretty_extension'] !== 'folder') {

      $check = \Drupal::database()->select('onehub', 'o')
        ->fields('o', ['oid'])
        ->condition('oid', $item['id'])
        ->execute();

      $result = $check->fetchField();

      $api = new OneHubApi();
      $folder_id = end($item['ancestor_ids']);
      $timestamp = new \DateTime($item['updated_at']);
      $ws_name = $api->getWorkspace($item['workspace_id']);
      $f_name = $api->getFolder($item['workspace_id'], $folder_id);

      $fields = [
        'entity_id' => 0,
        'workspace' => $item['workspace_id'],
        'workspace_name' => isset($ws_name['name']) ? $ws_name['name'] : '',
        'folder' => $folder_id,
        'folder_name' => isset($f_name['filename']) ? $f_name['filename'] : '',
        'filename' => $item['filename'],
        'timestamp' => $timestamp->getTimestamp(),
        'original_fid' => 0,
      ];

      if (!$result) {
        // Add our oid.
        $fields['oid'] = $item['id'];
        // Inject the record.
        \Drupal::database()->insert('onehub')
          ->fields($fields)
          ->execute();
      }
      else {
        // Update the record.
        \Drupal::database()->update('onehub')
          ->fields($fields)
          ->condition('oid', $item['id'])
          ->execute();
      }

      return $result;

    }

    // Nothing to process.
    return NULL;
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One file assimilated.', '@count files assimilated.');
      drupal_set_message($message, 'status', TRUE);
    }
    else {
      $error_operation = reset($operations);
      drupal_set_message(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))), 'status', TRUE);
    }

    // Redirect to Slack RTM page.
    $response = new RedirectResponse('/admin/config/services/onehub/update');
    $response->send();
  }
}