<?php
/**
 * @file
 * Contains \Drupal\royalslider\RoyalSliderOptionSetListBuilder.
 */

namespace Drupal\royalslider;


use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;


class RoyalSliderOptionSetListBuilder extends ConfigEntityListBuilder{
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Label
    $row['label'] = $this->getLabel($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $build = parent::render();

    $build['#empty'] = $this->t('There are no optionsets available.');
    return $build;
  }

}