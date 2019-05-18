<?php

namespace Drupal\flashpoint_course\Plugin\flashpoint_course;

use Drupal\Core\Plugin\PluginBase;
use Drupal\flashpoint_course\FlashpointCourseRendererInterface;
use Drupal\flashpoint_course\FlashpointCourseUtilities;
use Drupal\group\Entity\Group;
use Drupal\views\Views;

/**
 * @FlashpointCourseRenderer(
 *   id = "default_flashpoint_course_renderer",
 *   label = @Translation("DefaultFlashpointCourseRenderer"),
 * )
 */
class DefaultFlashpointCourseRenderer extends PluginBase implements FlashpointCourseRendererInterface {

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('Renders course using the default method.');
  }

  /**
   * @param $course
   * @return array
   */
  public static function renderCourse($course) {
    $course = Group::load($course);
    $description = $course->get('field_course_description')->getValue();
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');

    $form = [];

    if (FlashpointCourseUtilities::userIsEnrolled($course, \Drupal::currentUser()) || FlashpointCourseUtilities::isOpenAccessCourse($course)) {
      $form['course_container'] = [
        '#type' => 'vertical_tabs',
      ];
      if (isset($description[0])) {
        /*
         * Course Description.
         */
        $form['course_description'] = [
          '#type' => 'details',
          '#title' => t('Course Description'),
          '#group' => 'course_container',
          // Put this at the top
          '#weight' => -99,
        ];
        $form['course_description']['description'] = [
          '#type' => 'markup',
          '#markup' => $description[0]['value'],
        ];
      }
      /*
       * Course Modules
       */
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('flashpoint_course_module') && $moduleHandler->moduleExists('flashpoint_course_content')) {
        $args = [$course->id()];
        $module_view = Views::getView('flashpoint_manage_course_modules_in_course');
        $module_view->setArguments($args);
        $module_view->setDisplay('page_1');
        $module_view->preExecute();
        $module_view->execute();
        foreach ($module_view->result as $result) {
          $module = $result->_entity;
          $module = $module->getEntity();
          if ($module->isPublished()) {
            $form['module_' . $module->id()] = [
              '#type' => 'details',
              '#group' => 'course_container',
              '#title' => $module->label(),
              '#attributes' => ['class' => 'flashpoint-course-module-tab'],
            ];
            $instructional_content = $module->get('field_instructional_content')->getValue();
            $examination_content = $module->get('field_examination_content')->getValue();
            if (!empty($instructional_content)) {
              $form['module_' . $module->id()]['instructional_content'] = [
                '#theme' => 'item_list',
                '#list_type' => 'ul',
                '#title' => $flashpoint_config->getOriginal('flashpoint_course_module.instructional_text'),
                '#items' => [],
                '#attributes' => ['class' => 'flashpoint-course-module-content instructional-content'],
              ];
              foreach ($instructional_content as $ic) {
                $item = \Drupal\flashpoint_course_content\Entity\FlashpointCourseContent::load($ic['target_id']);
                $form['module_' . $module->id()]['instructional_content']['#items'][] = $item->renderListing();
              }
            }
            if (!empty($examination_content)) {
              $form['module_' . $module->id()]['examination_content'] = [
                '#theme' => 'item_list',
                '#list_type' => 'ul',
                '#title' => $flashpoint_config->getOriginal('flashpoint_course_module.examination_text'),
                '#items' => [],
                '#attributes' => ['class' => 'flashpoint-course-module-content examination-content'],
              ];
              foreach ($examination_content as $ec) {
                $item = \Drupal\flashpoint_course_content\Entity\FlashpointCourseContent::load($ec['target_id']);
                $form['module_' . $module->id()]['examination_content']['#items'][] = $item->renderListing();
              }
            }
          }
        }
      }
      /*
       * TODO: Course Content without modules
       */
    }
    else {
      if (isset($description[0]['value'])) {
        $form['course_description'] = [
          '#type' => 'markup',
          '#markup' => $description[0]['value'],
        ];
      }

    }

    return $form;
  }

}