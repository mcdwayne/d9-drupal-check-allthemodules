<?php

namespace Drupal\popup_onload;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Popup On Load entities.
 *
 * @ingroup popup_onload
 */
class PopupOnLoadListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\popup_onload\Entity\PopupOnLoad */
    $row['name']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => new Url(
        'entity.popup_onload.edit_form', [
          'popup_onload' => $entity->id(),
        ]
      ),
    ];
    return $row + parent::buildRow($entity);
  }

}
