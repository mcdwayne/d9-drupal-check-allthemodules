<?php

namespace Drupal\udheader\Controllers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for Ubuntu Drupal Header entity.
 *
 * @ingroup udheader
 */
class HeaderListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   *
   * Override the ::render() so that we can add our own content.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Ubuntu Drupal Headers are shown by the Ubuntu Drupal Header block.')
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Build the header and content lines for the Ubuntu Drupal Header list.
   */
  public function buildHeader() {
//    $header['id'] = $this->t('Header ID');
    $header['title'] = $this->t('Header name');
    $header['node'] = $this->t('Node type');
//    $header['left_image'] = $this->t('Left image');
//    $header['center_image'] = $this->t('Middle image');
//    $header['right_image'] = $this->t('Right image');
//    $header['center_text'] = $this->t('Middle text');
//    $header['right_text'] = $this->t('Right text');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\udheader\Entity\Header */
//    $row['id'] = $entity->id();
    $row['title'] = $entity->link();
    $row['node'] = $entity->node->getSetting('allowed_values')[$entity->node->value];
//    $row['left_image'] = $entity->left_image->value;
//    $row['center_image'] = $entity->center_image->value;
//    $row['right_image'] = $entity->right_image->value;
//    $row['center_text'] = $entity->center_text->value;
//    $row['right_text'] = $entity->right_text->value;

    return $row + parent::buildRow($entity);
  }
}
