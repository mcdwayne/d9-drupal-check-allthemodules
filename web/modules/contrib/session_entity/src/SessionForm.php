<?php

namespace Drupal\session_entity;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the session edit forms.
 */
class SessionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    // There is no entity to be found in the route, as we don't put the entity
    // ID in the path for the entity form.
    // Instead, attempt to get an entity from the user's private tempstore.
    // (This is a bit of hack -- it would be cleaner though require more code
    // to use a custom FormController class... todo: consider doing this in
    // future.)
    // The entity ID is immaterial -- the entity is fixed per user.
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load(NULL);

    if (empty($entity)) {
      // Create a new empty entity if nothing found.
      $values = [];
      // If the entity has bundles, fetch it from the route match.
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($bundle_key = $entity_type->getKey('bundle')) {
        if (($bundle_entity_type_id = $entity_type->getBundleEntityType()) && $route_match->getRawParameter($bundle_entity_type_id)) {
          $values[$bundle_key] = $route_match->getParameter($bundle_entity_type_id)->id();
        }
        elseif ($route_match->getRawParameter($bundle_key)) {
          $values[$bundle_key] = $route_match->getParameter($bundle_key);
        }
      }

      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    }

    return $entity;
  }


}
