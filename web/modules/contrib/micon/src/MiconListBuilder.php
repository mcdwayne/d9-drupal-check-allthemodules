<?php

namespace Drupal\micon;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\LinkGeneratorTrait;

/**
 * Provides a listing of Micon entities.
 */
class MiconListBuilder extends ConfigEntityListBuilder {
  use LinkGeneratorTrait;
  use MiconIconizeTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->micon('Package');
    $header['preview'] = $this->micon('Preview');
    $header['type'] = $this->micon('Type');
    $header['status'] = $this->micon('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\micon\Entity\Micon */
    $preview = [];
    if ($icons = $entity->getIcons()) {
      $count = count($icons) >= 12 ? 12 : count($icons);
      foreach (array_rand($icons, $count) as $key) {
        $preview[] = $icons[$key]->toRenderable();
      }
    }

    $row['label'] = $this->l(
      $this->t('<strong>@label</strong> <small>(.@machine)</small>', ['@label' => $entity->label(), '@machine' => $entity->id()]),
      new Url(
        'entity.micon.canonical', array(
          'micon' => $entity->id(),
        )
      )
    );
    $row['preview']['data'] = $preview;
    $row['type']['data']['#markup'] = '<small>' . $this->micon(strtoupper($entity->type())) . '</small>';
    $row['status'] = $entity->status() ? $this->micon('Published')->setIconOnly() : $this->micon('Unpublished')->setIconOnly();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no @label yet.', array('@label' => $this->entityType->getPluralLabel()));
    foreach ($this->load() as $micon) {
      $build['#attached']['library'][] = 'micon/micon.' . $micon->id();
    }
    return $build;
  }

}
