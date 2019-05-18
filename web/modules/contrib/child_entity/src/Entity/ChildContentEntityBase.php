<?php

namespace Drupal\child_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\child_entity\ChildContentEntityTypeQuery;

abstract class ChildContentEntityBase extends ContentEntityBase {

  private $query = NULL;

  /**
   * @return string parent entity key.
   */
  public function getParentColumn() {
    return $this->query()->getParentColumn();
  }

  /**
   * @return string route key of the parent entity in sub entity urls
   */
  public function getParentKeyInRoute() {
    return $this->query()->getParentKeyInRoute();
  }

  /**
   * @return \Drupal\child_entity\ChildContentEntityTypeQuery
   */
  private function query() {
    if ($this->query === NULL) {
      $this->query = new ChildContentEntityTypeQuery($this->getEntityType());
    }

    return $this->query;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getParentEntity() {
    return $this->get($this->getParentColumn())->entity;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return $this
   */
  public function setParentEntity(EntityInterface $entity) {
    $this->set($this->getParentColumn(), $entity->id());
    return $this;
  }

  /**
   * @inheritDoc
   */
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel) + [
        $this->getParentKeyInRoute() => $this->getParentEntity()
          ->id(),
      ];
    $params = $this->buildParentParams($params, $this, $this->query());
    return $params;
  }

  public function buildParentParams(array $parameters, ChildContentEntityBase $entity, ChildContentEntityTypeQuery $query) {
    if ($query->isParentAnotherChildEntity()) {
      $parentQuery = new ChildContentEntityTypeQuery($query->getParentEntityType());
      $parentQuery->getParentKeyInRoute();
      $parameters[$parentQuery->getParentKeyInRoute()] = $entity->getParentEntity()->id();
      $parameters = $this->buildParentParams($parameters, $entity->getParentEntity(), $parentQuery);
    }

    return $parameters;
  }
}