<?php

namespace Drupal\child_entity\Route;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\child_entity\ChildContentEntityTypeQuery;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Room entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class ChildContentEntityHtmlRouteProvider extends AdminHtmlRouteProvider {

  private function query($entity_type){
      return new ChildContentEntityTypeQuery($entity_type);
  }
  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);
    $query = $this->query($entity_type);
    $parentKey = $query->getParentKeyInRoute();
    $parentEntityTypeId = $query->getParentEntityTypeId();

    foreach ($collection as $key => $route) {
      $optionParameters = $route->getOption('parameters');
      if (!is_array($optionParameters)) {
        $optionParameters = [];
      }
      $optionParameters[$parentKey] = [
        'type' => 'entity:' . $parentEntityTypeId,
      ];
      $route->setOption('parameters', $optionParameters);
      $this->prepareWithParentEntities($route, $query);
      $collection->add($key, $route);
    }
    return $collection;
  }

  private function prepareWithParentEntities(Route $route, ChildContentEntityTypeQuery $query) {
    $parent_type = $query->getParentEntityType();
    $link = $parent_type->getLinkTemplate('canonical');
    $route->setPath($link . $route->getPath());

    $optionParameters = $route->getOption('parameters');
    if (!is_array($optionParameters)) {
      $optionParameters = [];
    }
    $optionParameters[$query->getParentKeyInRoute()] = [
      'type' => 'entity:' . $query->getParentEntityTypeId(),
    ];
    $route->setOption('parameters', $optionParameters);

    if ($query->isParentAnotherChildEntity()) {
      $parentQuery = $this->query($parent_type);
      $this->prepareWithParentEntities($route, $parentQuery);
    }
  }

}
