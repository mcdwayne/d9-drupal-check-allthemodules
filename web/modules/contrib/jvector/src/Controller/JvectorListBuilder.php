<?php
/**
 * @file
 * Contains \Drupal\jvector\Controller\JvectorListBuilder.
 */

namespace Drupal\jvector\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Jvectors.
 */
class JvectorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Jvector');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();

    // You probably want a few more properties here...

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    //$uri = $entity->uri();


    $operations['values'] = array(
      'title' => t('Show select values'),
      'weight' => -5,
      'url' => $entity->urlInfo('values-form'),
    );
    $operations['view'] = array(
      'title' => t('View'),
      'weight' => -30,
      'url' => $entity->urlInfo('view-form'),
    );
    return $operations;
  }

}