<?php

namespace Drupal\linky;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Linky entities.
 *
 * @ingroup linky
 */
class LinkyListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['link'] = $this->t('Link');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\linky\Entity\Linky */
    $row['link'] = $this->l(
      $entity->label(),
      new Url(
        'entity.linky.edit_form', [
          'linky' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
