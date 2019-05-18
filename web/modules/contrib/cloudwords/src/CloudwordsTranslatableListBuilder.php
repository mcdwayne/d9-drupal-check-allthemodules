<?php

namespace Drupal\cloudwords;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Cloudwords translatable entities.
 *
 * @ingroup cloudwords
 */
class CloudwordsTranslatableListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Cloudwords translatable ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\cloudwords\Entity\CloudwordsTranslatable */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      new Url(
        'entity.cloudwords_translatable.edit_form', [
          'cloudwords_translatable' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
