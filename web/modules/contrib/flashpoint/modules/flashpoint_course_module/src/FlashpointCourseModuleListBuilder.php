<?php

namespace Drupal\flashpoint_course_module;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Course module entities.
 *
 * @ingroup flashpoint_course_module
 */
class FlashpointCourseModuleListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Course module ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\flashpoint_course_module\Entity\FlashpointCourseModule */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.flashpoint_course_module.edit_form',
      ['flashpoint_course_module' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
