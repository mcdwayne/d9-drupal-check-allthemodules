<?php

namespace Drupal\pardot\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Pardot Score entities.
 *
 * @package Drupal\pardot\Controller
 *
 * @ingroup pardot
 */
class PardotScoreListBuilder extends ConfigEntityListBuilder {

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $header['label'] = $this->t('Score Label');
    $header['score_value'] = $this->t('Pardot Score Value');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['score_value'] = $entity->score_value;

    return $row + parent::buildRow($entity);
  }

  /**
   * Adds introduction to Pardot Score list.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>Pardot Scores for specific paths.</p>"),
    );
    $build[] = parent::render();
    return $build;
  }

}
