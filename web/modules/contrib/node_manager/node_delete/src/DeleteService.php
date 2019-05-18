<?php

namespace Drupal\node_delete;

class DeleteService
{

  /**
   * Function for deleting/unpublished node.
   */
  public function  deleteNode() {

    $nids = $this->getDeletableNode();

    if(!empty($nids)) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
      $delete_or_unpublished = \Drupal::config('node_delete.settings')->get('delete_or_unpublished');

      // Delete all expired nodes
      if($delete_or_unpublished) {
        foreach($nodes as $key => $node) {
          $id = $node->id();
          $title = $node->get("title")->getValue()[0]['value'];
          $message = t('Expired node deleted title => @title , node_id => @id', array('@title' => $title, '@id'=>$id));
          \Drupal::logger('node_delete')->notice($message);
          $node->delete();

          // Delete record as we don't need it.
          $query = \Drupal::database()->delete('table');
          $query->condition('id', $id);
          $query->execute();
        }

        // Unpublished node
      } elseif($delete_or_unpublished == 0) {
        foreach($nodes as $key => $node) {
          $id = $node->id();

          if($node->isPublished()) {
            $title = $node->get("title")->getString();
            $message = t('Expired node unpublished title => @title , node_id => @id', array('@title' => $title, '@id'=>$id));
            \Drupal::logger('node_delete')->notice($message);
            $node->set('status',0);
            $node->save();
          }
        }
      }
    }
  }

  /**
   * Get node that should be delete/unpublished.
   */
  public function getDeletableNode() {

    $db_connection = \Drupal::database();
    if ($db_connection->schema()->tableExists('node_notify')) {

      // Get current date
      $date = date("Y-m-d");

      $query = \Drupal::database()->select('node_notify', 'nn');
      $query->addField('nn', 'id');
      $query->condition('nn.status', 1);
      $data = $query->execute()->fetchAll();
      $expired_node = array();
      if(!empty($date)) {
        foreach ($data as $key => $value ) {
          $expired_node[] = $value->id;
        }
        return $expired_node;
      }
    }
    return NULL;
  }

}