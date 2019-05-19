<?php

namespace Drupal\slides_presentation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Presentation entities.
 *
 * @ingroup slides_presentation
 */
class PresentationListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Title');
    $header['author'] = $this->t('Author');
    $header['created'] = $this->t('Created date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\slides_presentation\Entity\Presentation */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $this->getLabel($entity),
      new Url(
        'entity.slides_presentation.edit_form', [
          'slides_presentation' => $entity->id(),
        ]
      )
    );
    $row['author'] = $entity->getOwner()->getUsername();
    $row['created'] = date('Y-m-d H:i', $entity->getCreatedTime());
    return $row + parent::buildRow($entity);
  }

}
