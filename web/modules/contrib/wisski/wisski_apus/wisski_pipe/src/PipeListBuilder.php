<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\PipeListBuilder.
 */

namespace Drupal\wisski_pipe;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of pipes.
 *
 * @see \Drupal\wisski_pipe\Entity\Pipe
 */
class PipeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
      $header['title'] = t('Pipe');
      $header['tags'] = t('Tags');
      $header['description'] = [
          'data' => t('Description'),
          'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\wisski_pipe\PipeInterface $pipe */
    $pipe = $entity;
    $row['label'] = $pipe->label();
    $row['tags'] = join(", ", $pipe->getTags());
    $row['description']['data'] = ['#markup' => $pipe->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit pipe');
    }

    $operations['processors'] = [
      'title' => t('Manage processors'),
      'weight' => 10,
      'url' => Url::fromRoute('wisski_pipe.processors', [
        'wisski_pipe' => $entity->id()
      ]),
    ];

    return $operations;
  }

}
