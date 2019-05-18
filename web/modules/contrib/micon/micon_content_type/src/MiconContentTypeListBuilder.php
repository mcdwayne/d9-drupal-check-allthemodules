<?php

namespace Drupal\micon_content_type;

use Drupal\node\NodeTypeListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of ContentType.
 */
class MiconContentTypeListBuilder extends NodeTypeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['icon'] = t('Icon');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $icon = micon_content_type_icon($entity);
    $row['icon']['data']['#markup'] = $icon ? micon()->setIcon($icon) : '';
    return $row + parent::buildRow($entity);
  }

}
