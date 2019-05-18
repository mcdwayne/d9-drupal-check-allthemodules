<?php

namespace Drupal\img_annotator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Zend\Diactoros\Response\JsonResponse;

class AnnotationsRetrieveAction extends ControllerBase {

	public function content() {
	  $account = \Drupal::currentUser();
	  $userId = $account->id();

	  // Ajax post data.
	  $postReq = \Drupal::request()->request->get('nid');
	  $nid = isset($postReq) ? $postReq : FALSE;
	  $node = entity_load('node', $nid);

	  // User permissions.
	  $canEdit = $account->hasPermission('img_annotator edit');
	  $canEditOwn = $account->hasPermission('img_annotator own edit');
	  $canCreate = $account->hasPermission('img_annotator create');
	  $canCreateOwn = $account->hasPermission('img_annotator own create');
	  $canView = $account->hasPermission('img_annotator view');
	  $canViewOwn = $account->hasPermission('img_annotator own view');

	  $canRetrieve = $canEdit || $canCreate || $canView;
	  $canRetrieveOwn = $canEditOwn || $canCreateOwn || $canViewOwn;

	  $nodeOwner = ($node->getOwnerId() == $userId) ? TRUE : FALSE;

	  // User can retrieve all node anno.
	  if ($nid && $canRetrieve) {
	    $anno_editable = TRUE;
	    $table = 'img_annotator';
	    $annotation_arr = array();

	    // Existing Annotation from table.
	    $existing_annotation = db_select($table, 'i')
	    ->fields('i', array('aid', 'annotation'))
	    ->condition('nid', $nid)
	    ->execute();

	    // User can edit.
	    if (!$canEdit && !($canEditOwn && $nodeOwner)) {
	      $anno_editable = FALSE;
	    }

	    // User can not view.
	    // It does not load library coz of code at .module alteration.

	    // Prepare annotation response.
	    while ($record = $existing_annotation->fetchObject()) {
	      $anno_val = Json::decode($record->annotation);
	      $anno_val['editable'] = $anno_editable;
	      $anno_val['aid'] = $record->aid;

	      $annotation_arr[] = $anno_val;
	    }

	    $response = Json::encode($annotation_arr);
	  }
	  // User can retrieve only own node anno.
	  elseif ($nid && $canRetrieveOwn) {

	    if ($nodeOwner) {
	      $anno_editable = TRUE;
	      $table = 'img_annotator';
	      $annotation_arr = array();

	      // Existing Annotation from table.
	      $existing_annotation = db_select($table, 'i')
	      ->fields('i', array('aid', 'annotation'))
	      ->condition('nid', $nid)
	      ->execute();

	      // User can edit
	      if (!$canEdit && !($canEditOwn && $nodeOwner)) {
	        $anno_editable = FALSE;
	      }

	      // User can not view.
	      // It does not load library coz of code at .module alteration.

	      // Prepare annotation response
	      while ($record = $existing_annotation->fetchObject()) {
	        $anno_val = Json::decode($record->annotation);
	        $anno_val['editable'] = $anno_editable;
	        $anno_val['aid'] = $record->aid;

	        $annotation_arr[] = $anno_val;
	      }

	      $response = Json::encode($annotation_arr);
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
