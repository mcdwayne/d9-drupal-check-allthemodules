<?php

namespace Drupal\external_entities\Routing;

use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides HTML routes for external entities.
 *
 * This class provides the following routes for external entities, with title
 * and access callbacks:
 * - canonical
 * - add-page
 * - add-form
 * - edit-form
 * - delete-form
 * - collection.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider.
 */
class ExternalEntityHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    // DefaultHtmlRouteProvider::getCollection() specifies that the admin
    // permission is required for viewing collections. We implement a separate
    // permission for external entity collection pages.
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $entity_type->getCollectionLabel();

      $route = new Route($entity_type->getLinkTemplate('collection'));
      $route
        ->addDefaults([
          '_entity_list' => $entity_type->id(),
          '_title' => $label->getUntranslatedString(),
          '_title_arguments' => $label->getArguments(),
          '_title_context' => $label->getOption('context'),
        ])
        ->setRequirement('_permission', "view {$entity_type->id()} external entity collection");

      return $route;
    }

    return NULL;
  }

}
