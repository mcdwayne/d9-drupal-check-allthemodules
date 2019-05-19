<?php

namespace Drupal\waterwheel\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get information about an bundle type.
 *
 * @RestResource(
 *   id = "bundle_type_resource",
 *   label = @Translation("Bundle type resource"),
 *   uri_paths = {
 *     "canonical" = "/entity/types/{entity_type}/{bundle}"
 *   }
 * )
 */
class BundleTypeResource extends EntityTypeResourceBase {

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param string $entity_type_id
   *   The entity type id for the request.
   * @param string $bundle_name
   *   The bundle machine name.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The resource response.
   */
  public function get($entity_type_id, $bundle_name) {
    return new ResourceResponse($this->getBundleInfo($entity_type_id, $bundle_name));
  }

  /**
   * Gets information about the bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return array
   *   The bundle info.
   */
  protected function getBundleInfo($entity_type_id, $bundle_name) {
    // @todo Load entity type in route system?
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      throw new NotFoundHttpException($this->t('No entity type found: @type', ['@type' => $entity_type_id]));
    }
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
      $bundle = $this->entityTypeManager->getStorage($bundle_entity_type_id)->load($bundle_name);
      if (!$bundle) {
        throw new NotFoundHttpException(
          $this->t(
            'No bundle "@bundle" found for entity type @type',
            ['@type' => $entity_type_id, '@bundle' => $bundle_name]
          )
        );
      }
      $bundle_info['label'] = $bundle->label();
      $bundle_info['fields'] = $this->getBundleFields($entity_type_id, $bundle_name);
      return $bundle_info;
    }
    else {
      $bundle_info['label'] = $entity_type->getLabel();
      $bundle_info['fields'] = $this->getBundleFields($entity_type_id, $bundle_name);
      return $bundle_info;
    }
  }

}
