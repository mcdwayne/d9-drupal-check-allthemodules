<?php 
/**
 * @file
 * Contains \Drupal\signed_nodes\Controller\SignedNodesController class.
 */

namespace Drupal\signed_nodes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for Signed Nodes module.
 */
class SignedNodesController extends ControllerBase {

  /**
   * Returns markup for our custom page.
   */
  public function adminlistPage() {
    $signed_nodes = signed_nodes_get_all();
    $rows = array();
    foreach ($signed_nodes as $snid => $snid_array) {
      $del = Url::fromRoute('signed_nodes.delete', ['id' => $snid])->toString();
      $edit = Url::fromRoute('signed_nodes.edit', ['id' => $snid])->toString();
      $row['data'] = [
        $snid_array->nid.' ('.$snid_array->year.')',
        t('<a href="@edit">Edit</a> | <a href="@del">Delete</a>', array('@edit' => $edit, '@del' => $del)),
      ];

      $rows[] = $row;
    }

    if ($rows) {
      $results = [
        '#theme' => 'table',
        '#header' => [t('Node Agreement'), t('Operations')],
        '#rows' => $rows,
      ];
      return $results;
    }
    else {
      $output = '<p>' . t('No signed nodes agreement have been added. Would you like to <a href="@link">add a new node agreement</a>?', array('@link' => Url::fromRoute('signed_nodes.add')->toString())) . '</p>';
    }

    return [
      '#markup' => $output,
    ]; 
  }

  /**
   * Returns markup for our custom page.
   */
  public function reportOverview() {
    $signed_nodes = signed_nodes_get_all();
    $rows = array();
    foreach ($signed_nodes as $snid => $snid_array) {
      $signed = Url::fromRoute('signed_nodes.reportsigned', ['id' => $snid])->toString();
      $pending = Url::fromRoute('signed_nodes.reportpending', ['id' => $snid])->toString();
      $result = db_select('node_field_data', 'n')
        ->fields('n')
        ->condition('nid', $snid_array->nid, '=')
        ->execute()
        ->fetchAssoc();
      $nodetitle = $result['title'];

      $row['data'] = [
        $nodetitle . ' (' . $snid_array->year . ')' ,
        t('<a href="@signed">Signed</a> | <a href="@pending">Pending</a>', array('@signed' => $signed, '@pending' => $pending)),
      ];
      $rows[] = $row;
    }

    if ($rows) {
      $results = [
        '#theme' => 'table',
        '#header' => [t('Node Agreement'), t('Report Operation')],
        '#rows' => $rows,
      ];
      return $results;
    }
    else {
      $output = '<p>' . t('No signed nodes agreement have been added. Would you like to <a href="@link">add a new node agreement</a>?', array('@link' => Url::fromRoute('signed_nodes.add')->toString())) . '</p>';
    }

    return [
      '#markup' => $output,
    ]; 
  }

  /**
   * Returns markup for our custom page.
   */
  public function reportSignedOverview(string $id = NULL) {
    $query = "SELECT sn.uid, u.name, u.mail FROM {signed_nodes_user} sn, {users_field_data} u WHERE sn.uid = u.uid and snid = :snid";
    return signed_nodes_report_all($id, $query);
  }

  /**
   * Returns markup for our custom page.
   */
  public function reportPendingOverview(string $id = NULL) {
    $query = "SELECT u.uid, u.name, u.mail FROM {users_field_data} u WHERE u.status = 1 AND u.uid NOT IN (SELECT snu.uid FROM {signed_nodes_user} snu WHERE snu.snid = :snid) AND (SELECT 1 FROM {signed_nodes} sn where sn.snid = :snid)";
    return signed_nodes_report_all($id, $query);
  }
}