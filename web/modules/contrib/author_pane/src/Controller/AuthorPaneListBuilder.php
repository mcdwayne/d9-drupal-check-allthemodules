<?php

/**
 * @file
 *
 * Contains Drupal\author_pane\Controller\AuthorPaneListBuilder
 */

namespace Drupal\author_pane\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;


class AuthorPaneListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['machine_name'] = $this->t('Machine Name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>Here you can define Author Panes and configure what information they display. A default pane is installed automatically.</p>"),
    );
    $build[] = parent::render();
    return $build;
  }

}