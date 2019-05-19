<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_course\Entity\CourseEnrollmentInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'CourseMaterialNavigationBlock' block.
 *
 * @Block(
 *   id = "course_material_navigation",
 *   admin_label = @Translation("Course material navigation block"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseMaterialNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $translation = \Drupal::service('entity.repository')
        ->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      $section = $course_wrapper->getSectionFromMaterial($node);
      $items = [];
      $storage = \Drupal::entityTypeManager()->getStorage('course_enrollment');
      $course_enrollments = $storage->loadByProperties([
        'sid' => $section->id(),
        'uid' => \Drupal::currentUser()->id(),
        'gid' => $course_wrapper->getCourse()->id(),
      ]);

      foreach ($course_enrollments as $key => $course_enrollment) {
        unset($course_enrollments[$key]);
        $course_enrollments[$course_enrollment->get('mid')->target_id] = $course_enrollment;
      }

      /** @var \Drupal\node\NodeInterface $material */
      foreach ($course_wrapper->getMaterials($section) as $material) {
        $item = [
          'label' => $material->label(),
          'url' => FALSE,
          'type' => $material->bundle(),
          'active' => FALSE,
          'number' => $course_wrapper->getMaterialNumber($material) + 1,
          'finished' => FALSE,
        ];

        if ($material->id() === $node->id()) {
          $item['active'] = TRUE;
        }

        if (isset($course_enrollments[$material->id()]) && $course_enrollments[$material->id()]->getStatus() === CourseEnrollmentInterface::FINISHED) {
          $item['finished'] = TRUE;
        }

        if ($course_wrapper->materialAccess($material, \Drupal::currentUser(), 'view')->isAllowed()) {
          $item['url'] = $material->toUrl();
        }

        $items[] = $item;
      }

      return [
        '#theme' => 'course_material_navigation',
        '#items' => $items,
        '#parent_course' => [
          'label' => $course_wrapper->getCourse()->label(),
          'url' => $course_wrapper->getCourse()->toUrl(),
        ],
        '#parent_section' => [
          'label' => $section->label(),
          'url' => $section->toUrl(),
        ],
      ];
    }
    else {
      $request = \Drupal::request();

      if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $title = \Drupal::service('title_resolver')->getTitle($request, $route);

        return [
          '#type' => 'page_title',
          '#title' => $title,
        ];
      }
      else {
        return [
          '#type' => 'page_title',
          '#title' => '',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      /** @var \Drupal\social_course\CourseWrapperInterface $course_wrapper */
      $course_wrapper = \Drupal::service('social_course.course_wrapper');
      $course_wrapper->setCourseFromMaterial($node);
      $tags = Cache::mergeTags($tags, $course_wrapper->getCourse()->getCacheTags());
      $tags = Cache::mergeTags($tags, $course_wrapper->getSectionFromMaterial($node)->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface && $node->id()) {
      $group = \Drupal::service('social_course.course_wrapper')
        ->setCourseFromMaterial($node)
        ->getCourse();

      return AccessResult::allowedIf($group instanceof GroupInterface);
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
