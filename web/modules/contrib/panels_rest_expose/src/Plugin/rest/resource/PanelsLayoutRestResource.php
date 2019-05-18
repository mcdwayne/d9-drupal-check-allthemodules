<?php

namespace Drupal\panels_rest_expose\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "panles_layout_rest_resource",
 *   label = @Translation("Panels Layout rest resource"),
 *   uri_paths = {
 *     "canonical" = "/panels/layout/{entity_id}"
 *   }
 * )
 */
class PanelsLayoutRestResource extends ResourceBase {
  /**
   * Responds to GET requests.
   *
   * Returns a Panels metadata for specified Node ID.
   *
   * @param int $id
   *   Panel's node ID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the log entry.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the panel entry was not found.
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when no panel Node ID was provided.
   */
  public function get($id = NULL) {
    // Checking if id is not null.
    if ($id) {
      // Fetching the panel's metadata based on the node id provided in URL 
      $record = db_query("SELECT * FROM {node__panelizer} WHERE entity_id = :entity_id", array(':entity_id' => $id))
        ->fetchAssoc();
      // If database query returns a result then return then return the response.
      if (!empty($record)) {
        // Return the result in ResourceResponse compatible format.
        return new ResourceResponse($this->formatResourceResponse($record));
      }
      // It throws an exception when no Panel is found.
      throw new NotFoundHttpException(t('panel with Node ID @id was not found', array('@id' => $id)));
    }
    // Throw an exception when No NodeID was provided.
    throw new BadRequestHttpException(t('No panel Node ID was provided'));
  }

  /**
   * This method returns the metadata of panels in an array.
   * 
   * @param mixed $defaultResource
   *  An array containing the panels's entry record.
   * 
   * @return type
   *  Return the Array of Panel's metadata.
   */
  public function formatResourceResponse($defaultResource = []) {
    $output = [];
    // If metadata has been there in the result returned by database query then unserialize that and return.
    if (!empty($defaultResource) && !empty($defaultResource['panelizer_panels_display'])) {
      // Unserializing the metadata.
      $output = unserialize($defaultResource['panelizer_panels_display']);
      // Retrieving blocks information from blocks metadata.
      $blocks = !empty($output['blocks']) ? $output['blocks'] : [];
      // Returning blocks in proper format.
      foreach ($blocks as $block) {
        $columns['blocks'][$block['region']][] = $block;
      }
      // Replacing the default blocks informatino with modified structure 
      if (!empty($columns['blocks'])) {
        $output['blocks'] = $columns['blocks'];
      }
      // Returning final output.
      return $output;
    }
  }
}
