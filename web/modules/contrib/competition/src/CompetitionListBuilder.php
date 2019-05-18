<?php

namespace Drupal\competition;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a listing of Competition entities.
 */
class CompetitionListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['cycle'] = $this->t('Cycle');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']['data'][] = Link::fromTextAndUrl($entity->label(), new Url(
      'entity.competition_entry.add_form', [
        'competition' => $entity->id(),
      ]
    ))->toRenderable();

    $archived = $entity->getCyclesArchived();
    if (count($archived) > 0) {
      $row['label']['data'][] = [
        '#markup' => ' (',
      ];

      $row['label']['data'][] = Link::fromTextAndUrl($this->t('archives'), new Url(
        'entity.competition_entry.archives_current', [
          'competition' => $entity->id(),
        ]
      ))->toRenderable();

      $row['label']['data'][] = [
        '#markup' => ')',
      ];
    }

    $row['cycle']['data'] = ['#markup' => $entity->getCycleLabel()];
    $row['status']['data'] = ['#markup' => $entity->getStatusLabel()];

    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('There are no @label yet.', [
      '@label' => $this->entityType->getPluralLabel(),
    ]);

    return $build;
  }

}
