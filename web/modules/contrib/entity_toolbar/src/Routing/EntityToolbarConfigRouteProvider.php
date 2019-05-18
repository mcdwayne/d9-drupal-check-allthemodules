<?php

namespace Drupal\entity_toolbar\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Routing\Route;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Provides routes forEntityToolbarConfig entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EntityToolbarConfigRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($route = $collection->get('entity.entity_toolbar.collection')) {
      $route->setDefault('_title', 'Entity Toolbars');
    }

    $toolbars = $this->entityTypeManager
      ->getStorage('entity_toolbar')
      ->loadMultiple();

    foreach ($toolbars as $toolbar) {

      try {
        $typeEntity = $this->entityTypeManager
          ->getDefinition($toolbar->get('bundleEntityId'));
      }
      catch (PluginNotFoundException $e) {
        \Drupal::messenger()->addMessage(new TranslatableMarkup('Error loading definition for the %type entity type in EntityToolbarConfigRouteProvider.', ['%type' => $toolbar->get('bundleEntityId')]), 'error');
        continue;
      }

      $collection_route = $this->getCollectionRoute($typeEntity);

      if (empty($collection_route)) {
        \Drupal::messenger()->addMessage(new TranslatableMarkup('Error loading collection route for the %type entity type in EntityToolbarConfigRouteProvider.', ['%type' => $toolbar->get('bundleEntityId')]), 'error');
        continue;
      }

      $route = new Route('/admin/entity_toolbar/' . $toolbar->id());

      $route->addDefaults([
        '_controller' => '\Drupal\entity_toolbar\Controller\ToolbarController::lazyLoad',
        '_title' => 'Toolbar',
        'toolbar' => $toolbar->id(),
      ]);

      $permission = $collection_route->getRequirement('_permission');

      if (empty($permission)) {
        $permission = 'administer site configuration';
      }

      $route->setRequirements([
        'toolbar' => '[a-zA-Z]+',
        '_permission' => $permission,
      ]);

      $route->setOptions([
        'parameters' => [
          'toolbar' => [
            'type' => 'entity:entity_toolbar',
          ],
        ],
      ]);

      $collection->add('entity_toolbar.ajax.' . $toolbar->id(), $route);
    }

    return $collection;
  }

}
