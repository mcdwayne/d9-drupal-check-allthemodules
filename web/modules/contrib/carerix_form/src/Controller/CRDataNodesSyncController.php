<?php

namespace Drupal\carerix_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;

/**
 * Class CRDataNodesSyncController.
 *
 * @package Drupal\carerix_form\Controller
 */
class CRDataNodesSyncController extends ControllerBase {

  /**
   * Sync action Carerix data nodes with stored.
   *
   * @param array $data
   *   An array of carerix data nodes from the Drupal database.
   * @param array $dataNode
   *   A single carerix data node instance.
   * @param string $dataNodeType
   *   A carerix data node type.
   * @param int $ts
   *   A timestamp.
   * @param string $operationDetails
   *   Operation details.
   * @param $context
   *   The batch context array.
   */
  public static function syncDataNode(array $data, array $dataNode, $dataNodeType, $ts, $operationDetails, &$context) {

    $db = \Drupal::database();
    $result = $dataNode['dataNodeID'] . ' : ' . Html::escape($dataNode['value']);

    // Check existing for "data node id".
    if (empty($data) || !in_array($dataNode['dataNodeID'], array_keys($data))) {
      // Data node id does not exist. Now check existing value before insert.
      if (in_array($dataNode['value'], $data)) {
        $db->delete('carerix_data_nodes')
          ->condition('data_node_value', $dataNode['value'], '=')
          ->execute();
      }
      // New data node.
      $db->insert('carerix_data_nodes')
        ->fields([
          'data_node_id' => $dataNode['dataNodeID'],
          'data_node_type' => $dataNodeType,
          'data_node_value' => $dataNode['value'],
          'timestamp' => $ts,
        ])
        ->execute();
      // Add created.
      $context['results']['created'][] = $result;
    }
    // Data node id exists already.
    else {
      // Check if value still corresponds with stored value.
      if ($data[$dataNode['dataNodeID']] != $dataNode['value']) {
        // If not, then do update.
        $db->update('carerix_data_nodes')
          ->fields([
            'data_node_value' => $dataNode['value'],
            'timestamp' => $ts,
          ])
          ->condition('data_node_id', $dataNode['dataNodeID'], '=')
          ->execute();
        // Add updated.
        $context['results']['updated'][] = $result;
      }
      // Else skip.
      else {
        // Update timestamp.
        $db->update('carerix_data_nodes')
          ->fields(['timestamp' => $ts])
          ->condition('data_node_id', $dataNode['dataNodeID'], '=')
          ->execute();
        // Add skipped.
        $context['results']['skipped'][] = $result;
      }
    }

    // Add message.
    $context['message'] = \Drupal::translation()->translate('Syncing data node @title', [
      '@title' => $dataNode['value'],
    ]) . ' ' . $operationDetails;
  }

  /**
   * Post cleanup action.
   *
   * @param string $dataNodeType
   *   Carerix data node type.
   * @param int $ts
   *   A timestamp.
   * @param string $operationDetails
   *   Operation details.
   * @param $context
   *   The batch context array.
   */
  public static function cleanUpDataNodes($dataNodeType, $ts, $operationDetails, &$context) {
    // Delete data nodes.
    $deleteCount = \Drupal::database()->delete('carerix_data_nodes')
      ->condition('data_node_type', $dataNodeType, '=')
      ->condition('timestamp', $ts, '<')
      ->execute();
    // Add message.
    $context['message'] = \Drupal::translation()->translate('Cleaning up data nodes') . ' ' . $operationDetails;
    $context['results']['deleted'] = $deleteCount;
  }

  /**
   * Finish callback.
   *
   * @param bool $success
   *   TRUE on success.
   * @param $results
   *   Results array.
   * @param $operations
   *   Operations array.
   */
  public static function syncFinishedCallback($success, $results, $operations) {
    if ($success) {
      foreach ($results as $resultType => $rows) {
        switch ($resultType) {
          case 'created':
            drupal_set_message(\Drupal::translation()->formatPlural(
              count($rows),
              '1 item created.', '@count items created.'
            ));
            break;

          case 'updated':
            drupal_set_message(\Drupal::translation()->formatPlural(
              count($rows),
              '1 item updated.', '@count items updated.'
            ));
            break;

          case 'skipped':
            drupal_set_message(\Drupal::translation()->formatPlural(
              count($rows),
              '1 item skipped.', '@count items skipped.'
            ));
            break;

          case 'deleted':
            if ($rows) {
              drupal_set_message(\Drupal::translation()->formatPlural(
                $rows,
                '1 item deleted.', '@count items deleted.'
              ));
            }
            break;
        }
      }
    }
    else {
      $message = t('Finished with an error.');
      drupal_set_message($message);
    }
  }

}
