<?php

namespace Drupal\waterwheel\Plugin\rest\resource;

use Drupal\waterwheel\Plugin\rest\EntityTypeResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get a list of entity types.
 *
 * @RestResource(
 *   id = "entity_types_list_resource",
 *   label = @Translation("Entity types list resource"),
 *   uri_paths = {
 *     "canonical" = "/entity/types"
 *   }
 * )
 */
class EntityTypesListResource extends EntityTypeResourceBase {

  /**
   * Returns information about all entity types on the systems.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    return new ResourceResponse($this->getEntityTypesData());
  }

  /**
   * Gets the information about entity types on the site.
   *
   * @return array
   *   Information about entity types.
   */
  protected function getEntityTypesData() {
    $type_infos = [];
    /** @var \Drupal\waterwheel\Plugin\rest\resource\EntityTypeResource $bundle_resource */
    $bundle_resource = $this->resourceManager->createInstance('bundle_type_resource');
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $bundle_resource->routes()->getIterator()->current();
    $path = $route->getPath();
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $methods = $this->getEntityMethods($entity_type_id);
      if (empty($methods)) {
        continue;
      }
      $meta_type = $this->getMetaEntityType($entity_type);
      $type_infos[$entity_type_id] = [
        'label' => $entity_type->getLabel(),
        'type' => $meta_type,
        // @todo Should we only returns entities that have methods enabled.
        'methods' => $this->getEntityMethods($entity_type_id),
        // @todo What other info?
      ];

      if ($meta_type == 'content') {
        if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
          $bundles = $this->entityTypeManager->getStorage($bundle_entity_type_id)->loadMultiple();
          $type_infos[$entity_type_id]['bundles'] = array_keys($bundles);
          $type_infos[$entity_type_id]['more'] = str_replace('{entity_type}', $entity_type_id, $path);
        }
        else {
          // Content entity types with bundles use a default bundle
          // which is the same as entity type id.
          $type_infos[$entity_type_id]['more'] = "entity/types/$entity_type_id/$entity_type_id";
        }
      }
      else {
        $type_infos[$entity_type_id]['more'] = "entity/types/$entity_type_id";
      }

    }

    return $type_infos;
  }

}
