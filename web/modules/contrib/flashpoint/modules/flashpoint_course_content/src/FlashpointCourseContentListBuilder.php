<?php

namespace Drupal\flashpoint_course_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Flashpoint course content entities.
 *
 * @ingroup flashpoint_course_content
 */
class FlashpointCourseContentListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Flashpoint course content ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\flashpoint_course_content\Entity\FlashpointCourseContent */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.flashpoint_course_content.edit_form',
      ['flashpoint_course_content' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
