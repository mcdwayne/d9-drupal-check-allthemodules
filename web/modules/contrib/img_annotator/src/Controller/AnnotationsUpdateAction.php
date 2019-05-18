<?php

namespace Drupal\img_annotator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Zend\Diactoros\Response\JsonResponse;

class AnnotationsUpdateAction extends ControllerBase {

	public function content() {
	  $account = \Drupal::currentUser();
	  $userId = $account->id();

	  // Ajax post data.
	  $postReq = \Drupal::request()->request->get('annotation');
	  $annotation = isset($postReq) ? $postReq : FALSE;
	  $aid = isset($annotation['aid']) ? $annotation['aid'] : FALSE;
	  $nid = isset($annotation['nid']) ? $annotation['nid'] : FALSE;

	  // User permissions.
	  $canEdit = $account->hasPermission('img_annotator edit');
	  $canEditOwn = $account->hasPermission('img_annotator own edit');

	  // User can update all node anno.
	  if ($annotation && $aid && $nid && $canEdit) {
	    $table = 'img_annotator';
	    $annotationEncoded = Json::encode($annotation);

	    $rowCount = db_update($table)
	    ->fields(array('uid' => $userId, 'annotation' => $annotationEncoded, 'updated' => time()))
	    ->condition('aid', $aid)
	    ->execute();

	    $response = (bool) $rowCount;
	  }
	  // User can update only own node anno.
	  elseif ($annotation && $aid && $nid && $canEditOwn) {
	    // Check if account is owner of the node feedback.
      $node = entity_load('node', $nid);

      // Allow only if user is the owner of the node.
      if ($node->getOwnerId() == $userId) {
	      $table = 'img_annotator';
	      $annotationEncoded = Json::encode($annotation);

	      $rowCount = db_update($table)
	      ->fields(array('uid' => $userId, 'annotation' => $annotationEncoded, 'updated' => time()))
	      ->condition('aid', $aid)
	      ->execute();

	      $response = (bool) $rowCount;
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
