<?php

namespace Drupal\micon_paragraphs;

use Drupal\paragraphs\Controller\ParagraphsTypeListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of ParagraphsType.
 */
class MiconParagraphsTypeListBuilder extends ParagraphsTypeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    if (isset($row['icon_file'])) {
      $icon = micon_paragraphs_icon($entity);
      if ($icon) {
        $row['icon_file'] = [];
        $row['icon_file']['data']['#markup'] = micon()->setIcon($icon);
      }
    }
    return $row;
  }

}
