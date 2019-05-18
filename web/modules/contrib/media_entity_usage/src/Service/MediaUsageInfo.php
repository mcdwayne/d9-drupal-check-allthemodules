<?php

namespace Drupal\media_entity_usage\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_entity\MediaInterface;

class MediaUsageInfo {

  use StringTranslationTrait;

  /**
   * @param MediaInterface $media
   *
   * @return int
   */
  public function getRefsCount(MediaInterface $media) {
    $query = \Drupal::database()->query(
      "SELECT COUNT(DISTINCT mu.eid, mu.entity_type, mu.bundle_name) AS usage_count FROM {media_usage} mu WHERE mu.mid = :mid",
      [':mid' => $media->id()]
    );
    return $query->fetchField() ?: 0;
  }

  /**
   * @param MediaInterface $media
   *
   * @return EntityInterface[]
   */
  public function getRefsList(MediaInterface $media) {
    $refs = [];
    $query = \Drupal::database()->query(
      "SELECT DISTINCT mu.eid, mu.entity_type, mu.bundle_name FROM {media_usage} mu WHERE mu.mid = :mid",
      [':mid' => $media->id()]
    );
    $results = $query->fetchAll();
    foreach ($results as $result) {
      $refs[] = \Drupal::entityTypeManager()
        ->getStorage($result->entity_type)
        ->load($result->eid);
    }
    return $refs ?: false;
  }

  /**
   * @param EntityInterface[] $refs
   *
   * @return array
   */
  public function buildRefsTable(array $refs) {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundleInfo */
    $bundleInfo =  \Drupal::service("entity_type.bundle.info");
    $rows = [];
    foreach ($refs as $ref) {
      $rows[] = [
        $ref->hasLinkTemplate('canonical') ? $ref->toLink($ref->label(), 'canonical', ['attributes' => ['target' => '_blank']]) : $ref->label(),
        $ref->getEntityType()->getLabel(),
        $bundleInfo->getBundleInfo($ref->getEntityType()->id())[$ref->bundle()]['label'],
      ];
    }
    return [
      '#type' => 'table',
      //'#caption' => $this->t('List of references'),
      '#header' => [
        $this->t('Reference'),
        $this->t('Entity type'),
        $this->t('Bundle name'),
      ],
      '#rows' => $rows,
    ];
  }
}