<?php

namespace Drupal\img_annotator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Zend\Diactoros\Response\JsonResponse;

class AnnotationsSaveAction extends ControllerBase {

  public function content() {
    $account = \Drupal::currentUser();
    $userId = $account->id();

    // Ajax post data.
    $postReq = \Drupal::request()->request->all();
    $nid = isset($postReq['nid']) ? $postReq['nid'] : FALSE;
    $img_field = isset($postReq['img_field']) ? $postReq['img_field'] : FALSE;
    $annotation = isset($postReq['annotation']) ? $postReq['annotation'] : FALSE;

    // User permissions.
    $validParams = $nid && $img_field && $annotation;
    $canCreateAll = $account->hasPermission('img_annotator create');
    $canCreateOwn = $account->hasPermission('img_annotator own create');

    // User can create all node anno.
    if ($validParams && $canCreateAll) {
      $table = 'img_annotator';
      $annotationEncoded = Json::encode($annotation);

      $queryVals = array(
          'nid' => $nid,
          'uid' => $userId,
          'field' => $img_field,
          'annotation' => $annotationEncoded,
          'updated' => time(),
      );
      $aid = db_insert($table)->fields($queryVals)->execute();
      $response = $aid;
    }
    // User can create only own node anno.
    elseif ($validParams && $canCreateOwn) {
      // Check if account is owner of the node feedback.
      $node = entity_load('node', $nid);

      // Allow only if user is the owner of the node.
      if ($node->getOwnerId() == $userId) {

        $table = 'img_annotator';
        $annotationEncoded = drupal_json_encode($annotation);

        $queryVals = array(
            'nid' => $nid,
            'uid' => $userId,
            'field' => $img_field,
            'annotation' => $annotationEncoded,
            'updated' => time()
        );
        $aid = db_insert($table)->fields($queryVals)->execute();
        $response = $aid;
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
