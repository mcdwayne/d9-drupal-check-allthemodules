<?php

namespace Drupal\img_annotator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Zend\Diactoros\Response\JsonResponse;

class AnnotationsDeleteAction extends ControllerBase {

  public function content() {
    $account = \Drupal::currentUser();
    $userId = $account->id();

    // Ajax post data.
    $postReq = \Drupal::request()->request->get('annotation');
    $annotation = isset($postReq) ? $postReq : FALSE;
    $aid = isset($annotation['aid']) ? $annotation['aid'] : FALSE;
    $nid = isset($annotation['nid']) ? $annotation['nid'] : FALSE;

    // User permissions.
    $canDelete = $account->hasPermission('img_annotator edit');
    $canDeleteOwn = $account->hasPermission('img_annotator own edit');

    // User can delete all node anno.
    if ($aid && $nid && $canDelete) {

      $table = 'img_annotator';
      $deleted_count = db_delete($table)
      ->condition('aid', $aid)
      ->execute();

      $response = (bool) $deleted_count;
    }
    // User can delete only own node anno.
    elseif ($aid && $nid && $canDeleteOwn) {

      $node = entity_load('node', $nid);
      if ($node->getOwnerId() == $userId) {

        $table = 'img_annotator';
        $deleted_count = db_delete($table)
        ->condition('aid', $aid)
        ->execute();

        $response = (bool) $deleted_count;
      }
      else {
        $response = FALSE;
      }
    }
    else {
      $response = FALSE;
    }

    return new JsonResponse($response);
  }

}
