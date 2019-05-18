<?php

namespace Drupal\meu_paragraphs\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media_entity_usage\Service\MediaUsagePersistance;
use Drupal\paragraphs\ParagraphInterface;

class ParagraphPersistance extends MediaUsagePersistance {

  public function store(EntityInterface $entity, array $media = []) {
    parent::store($entity, $media);
    if ($entity instanceof ParagraphInterface) {
      $queryFields = ['mid', 'entity_type', 'bundle_name', 'eid', 'langcode', 'submodule'];
      $parent = $entity->getParentEntity();
      foreach ($media as $mid) {
        $queryValues = [
          $mid,
          $parent->getEntityType()->id(),
          $parent->bundle(),
          $parent->id(),
          $parent->language()->getId(),
          $this->submodule,
        ];
        \Drupal::database()->insert('media_usage')
          ->fields($queryFields)
          ->values($queryValues)
          ->execute();
      }
    }
    return true;
  }

  public function purge(EntityInterface $entity) {
    parent::purge($entity);
    if ($entity instanceof ParagraphInterface) {
      $parent = $entity->getParentEntity();
      $query = \Drupal::database()->delete('media_usage')
        ->condition('submodule', $this->submodule)
        ->condition('entity_type', $parent->getEntityType()->id())
        ->condition('bundle_name', $parent->bundle())
        ->condition('eid', $parent->id())
        ->condition('langcode', $parent->language()->getId())
      ;
      $query->execute();
    }
    return true;
  }
}