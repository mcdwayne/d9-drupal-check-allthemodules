<?php

namespace Drupal\entity_access\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessCheck as EntityAccessCheckBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;

/**
 * Class EntityAccessCheck.
 */
class EntityAccessCheck extends EntityAccessCheckBase {

  /**
   * {@inheritdoc}
   *
   * Available improvement:
   * @code
   * example.route:
   *   path: /admin/structure/taxonomy/manage/{taxonomy_vocabulary}/update
   *   requirements:
   *     _entity_access: taxonomy_vocabulary:BUNDLE_NAME.update
   * @endcode
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Split the entity type and the operation.
    list($entity_type, $operation) = explode('.', $route->getRequirement('_entity_access'));

    $entity_type = explode(':', $entity_type);
    $parameters = $route_match->getParameters();

    // If there is valid entity of the given entity type, check its access.
    if ($parameters->has($entity_type[0])) {
      $entity = $parameters->get($entity_type[0]);

      if ($entity instanceof EntityInterface) {
        // Verify the bundle, if it was specified.
        if (isset($entity_type[1])) {
          // @link https://www.drupal.org/node/2835597#comment-11825373
          if (($entity instanceof ContentEntityInterface ? $entity->bundle() : $entity->id()) !== $entity_type[1]) {
            return AccessResult::neutral();
          }
        }

        return $entity->access($operation, $account, TRUE);
      }
    }

    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return AccessResult::neutral();
  }

}
