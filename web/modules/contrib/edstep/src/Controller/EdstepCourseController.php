<?php

namespace Drupal\edstep\Controller;

use Drupal\edstep\Entity\EdstepCourse;
use Drupal\Core\Controller\ControllerBase;

class EdstepCourseController extends ControllerBase {

  public function add() {
    $response = \Drupal::service('edstep.edstep')->authorize();
    if($response) {
      return $response;
    }

    // TODO: Replace with `loadByProperties`
    $query = \Drupal::database()->select('edstep_course', 'ec');
    $query->addField('ec', 'course_id');
    $query->condition('ec.uid', \Drupal::currentUser()->id());
    $added_edstep_course_ids = $query->execute()->fetchCol();

    $client = \Drupal::service('edstep.edstep')->getClient();
    $remote_courses = $client->courses();

    $edstep_courses = [];

    foreach($remote_courses as $remote_course) {
      if(in_array($remote_course->id, $added_edstep_course_ids)) {
        continue;
      }
      $edstep_course = $this->entityManager()->getStorage('edstep_course')->create();
      $edstep_course->setRemote($remote_course);
      $edstep_courses[] = $edstep_course;
    }

    usort($edstep_courses, function($edstep_course) {
      return $edstep_course->label();
    });

    foreach($edstep_courses as $edstep_course) {
      $render_controller = \Drupal::entityManager()->getViewBuilder('edstep_course');
      $build[] = $render_controller->view($edstep_course, 'manage');
    }

    if(empty($build)) {
      $message = (
        empty($added_edstep_course_ids)
        ? $this->t('You have no courses on EdStep that can be added.')
        : $this->t('You have already added all your courses from EdStep.')
      );
      $build = [
        '#markup' => $message,
      ];
    }

    return $build;
  }

  public function getTitle(EdstepCourse $edstep_course) {
    return $edstep_course->getTitle();
  }

  public function getActivityTitle(EdstepCourse $edstep_course, $section_id, $activity_id) {
    return $edstep_course->getRemote()->section($section_id)->activity($activity_id)->title;
  }

  public function viewActivity(EdstepCourse $edstep_course, $section_id, $activity_id) {
    return [
      '#theme' => 'edstep_course_activity',
      '#edstep_course' => $edstep_course,
      '#section_id' => $section_id,
      '#activity_id' => $activity_id,
    ];
  }

  public function viewResult(EdstepCourse $edstep_course) {
    return [
      '#markup' => $this->t('You have completed the course %title.', array('%title' => $edstep_course->getRemote()->title)),
    ];
  }

  public function overview() {
    return [];
  }

}
