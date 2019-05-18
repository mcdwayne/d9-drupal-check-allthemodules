<?php

namespace Drupal\annotation_store\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for annotation_store routes.
 */
class AnnotationStoreController extends ControllerBase {

  /**
   * Routing callback - annotation create.
   */
  public function annotationStoreCreate($id, Request $request) {

    $response = array();
    // Get the request data.
    $received = $request->getContent();
    $annotation_data = json_decode($received);
    // Create the annotation entity.
    $entity->content['data']['annotations'] = $annotation_data;
    \Drupal::moduleHandler()->invokeAll('annotation_store_create_endpoint_output_alter', array(&$entity, $id));
    $annotation_data = $entity->content['data']['annotations'];
    $response = $this->annotationApiCreate($annotation_data, $id);
    // Add watchdog.
    //\Drupal::logger('Annotation Store')->info('Created entity %type with ID %id.', array('%type' => $entity->getEntityTypeId(), '%id' => $entity->id()));
    //$response['id'] = $entity->id();
    return  new JsonResponse($response);
  }

  /**
   * Routing callback - annotation update and delete.
   */
  public function annotationStoreApi($id, Request $request) {
    $response = array();
    // Fetch the request method.
    $request_method = $request->getMethod();
    // Depending on the request method, perform update/delete/search operations.
    switch ($request_method) {

      case 'GET':
        $response = $this->annotationApiSearch($id, $request);
        break;

      case 'PUT' || 'PATCH':
        $response = $this->annotationApiUpdate($id, $request);
        break;

      case 'DELETE':
        $response = $this->annotationApiDelete($id, $request);
        break;
    }
    return new JsonResponse($response);
  }

  /**
   * Annotation search - Returns list of annotations.
   */
  public function annotationApiSearch($id, $request) {

    $output = array();
    $resource_entity_id = $id;
    // Load the Entity.
    $entity = \Drupal::entityTypeManager()->getStorage('annotation_store')->load($resource_entity_id);
    // get annotations from annotation store
    $annotations = $this->getSearchAnnotation($resource_entity_id);
    $entity->content['data']['annotations'] = $annotations;
    \Drupal::moduleHandler()->invokeAll('annotation_store_search_endpoint_output_alter', array(&$entity));
    $output = $entity->content['data']['annotations'];
    return $output;
  }

  /**
   * Gathers annotations added against an entity.
   */
  public function getSearchAnnotation($resource_entity_id) {
    $annotations = array();
    $ids = \Drupal::entityQuery('annotation_store')->condition('resource_entity_id', $resource_entity_id)->execute();
      foreach ($ids as $key => $value) {
        $records = \Drupal::entityTypeManager()->getStorage('annotation_store')->load($value);
        $annotation_object = json_decode($records->data->value);
        $annotations[] = array(
          'data' => $annotation_object,
          'id' => $value,
          'text' => $records->text->value,
        );
      }
     return $annotations;
  }

  /**
   * Annotation create as entity.
   */
  public function annotationApiCreate($annotation_data, $id) {
    // Get the site default language.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Save only if annotation data is present.
    if ($annotation_data->text) {
      $entity = entity_create('annotation_store', array(
        'type' => $annotation_data->media,
        'language' => $language,
        'data' => json_encode($annotation_data->data),
        'uri' => $annotation_data->uri,
        'text' => $annotation_data->text,
        'resource_entity_id' => $annotation_data->id,
      ));
      $entity->save();
      $annotation_data->id = $entity->id();
      $this->updateAnnotation($entity->id(), $annotation_data, 'onCreate');
    }
    return $entity;
  }

  /**
   * Annotation update - loads posted data, returns data as JSON object.
   */
  public function annotationApiUpdate($id, $request) {
    $response = array();
    $entity = array();
    // Get the request data.
    $received = $request->getContent();
    $annotation_data = json_decode($received);
    if ($id) {
      $entity = $this->updateAnnotation($id, $annotation_data, 'onUpdate');
    }
    $response['id'] = $entity->id();
    return $response;
  }

  /**
   * Annotation update - deletes the entity based on the id passed.
   */
  public function annotationApiDelete($id) {
    $response = array();
    if ($id) {
      $entity = \Drupal::entityTypeManager()->getStorage('annotation_store')->load($id);
      $entity->delete();
    }
    $response['id'] = $id;
    return $response;
  }

  /**
   * Annotation update callback.
   */
  public function updateAnnotation($id, $data, $flag) {
    $entity = entity_load('annotation_store', $id);
    if ($flag == 'onUpdate') {
      $entity->text->value = $data->text;
      $entity->changed->value = time();
    }
    $entity->data->value = json_encode($data->data);
    $entity->save();
    return $entity;
  }

}
