<?php

namespace Drupal\broken_link;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Broken link redirect entity entities.
 */
class BrokenLinkRedirectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['pattern'] = $this->t('Broken link pattern');
    $header['redirect_path'] = $this->t('Redirect path');
    $header['enabled'] = $this->t('Enabled');
    $header['weight'] = $this->t('Weightage');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['pattern'] = $entity->get('pattern')->get(0)->getValue()['value'];
    $row['redirect_path'] = $entity->get('redirect_path')->get(0)->getValue()['value'];
    $row['enabled'] = $entity->get('enabled')->get(0)->getValue()['value'];
    $row['weight'] = $entity->get('weight')->get(0)->getValue()['value'];
    return $row + parent::buildRow($entity);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity weightage.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('weight');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
