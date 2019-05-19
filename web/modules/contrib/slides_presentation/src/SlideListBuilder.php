<?php

namespace Drupal\slides_presentation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Slide entities.
 *
 * @ingroup slides_presentation
 */
class SlideListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Title');
    $header['presentation'] = $this->t('Presentation');
    $header['author'] = $this->t('Author');
    $header['created'] = $this->t('Created date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\slides_presentation\Entity\Slide */

    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $this->getLabel($entity),
      new Url(
        'entity.slides_slide.edit_form', [
          'slides_slide' => $entity->id(),
        ]
      )
    );
    $presentation = $entity->getPresentation();
    $row['presentation'] = $this->l(
      $this->getLabel($presentation),
      new Url(
        'entity.slides_presentation.canonical', [
          'slides_presentation' => $entity->get('presentation_id')->target_id,
        ]
      )
    );
    $row['author'] = $entity->getOwner()->getUsername();
    $row['created'] = date('Y-m-d H:i', $entity->getCreatedTime());
    return $row + parent::buildRow($entity);
  }

}
