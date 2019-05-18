<?php

namespace Drupal\flashpoint_community_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Flashpoint community content entities.
 *
 * @ingroup flashpoint_community_content
 */
class FlashpointCommunityContentListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Flashpoint community content ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContent */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.flashpoint_community_content.edit_form',
      ['flashpoint_community_content' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
