<?php

namespace Drupal\ena;

use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;

/**
 * @file
 * EntityNoteAuditData.
 */
class EntityNoteAuditData {

  /**
   * @param $etid
   * @return string
   */
  public static function accessData(&$etid) {
    $query = \Drupal::database()->select('ena_notes', 'e')
      ->fields('e', [
        'uid',
        'message',
        'created',
      ])
      ->orderBy('created', 'DESC')
      ->condition('etid', $nid)
      ->execute();
    $results = $query->fetchAll();
    $output = '';
    if (count($results) == 0) {
      $output .= '<div class="alert alert-warning" role="alert">';
      $output .= t('There are entity notes.');
      $output .= '</div>';
      return $output;
    }
    else {
      // @todo: move to twig.
      foreach ($results as $row) {
        $account = User::load($row->uid);
        $name = $account->getUsername();
        $created = \Drupal::service('date.formatter')->format($row->created, 'medium');
        // Build layout.
        $output .= '<div class="col-md-12 bg-info" style="margin-bottom:10px;">';
        $output .= '<div class="col-md-12">';
        $output .= t('Posted by <strong>%name</strong> on <strong>%created</strong>', ['%name' => $name, '%created' => $created]);
        $output .= '<div class="row"></div>';
        $output .= $row->message;
        $output .= '</div></div>';
      }
      return $output;
    }
  }

  /**
   * Insert data from post.
   *
   * @param $data
   */
  public static function insertData($data) {
    try {
      \Drupal::database()->insert('ena_notes')
        ->fields([
          'uid' => $data['uid'],
          'etid' => $data['etid'],
          'created' => $data['created'],
          'message' => $data['message'],
        ])
        ->execute();
    }
    catch (\InvalidArgumentException $e) {
      $e->getMessage();
    }
  }

}
