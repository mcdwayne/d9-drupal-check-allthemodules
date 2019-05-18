<?php

namespace Drupal\node_notify;

class DataManager
{

  public function getNotifyNode() {

    $db_connection = \Drupal::database();
    if ($db_connection->schema()->tableExists('node_notify')) {

      $query = \Drupal::database()->select('node_notify', 'nn');
      $query->addField('nn', 'id');
      $query->condition('nn.status', 0);
      $nid = $query->execute()->fetchAll();
      $node_id = [];
      foreach ($nid as $key => $value ) {
        $node_id[] = $value->id;
      }
      return $node_id;
      }
    return NULL;
  }

}
