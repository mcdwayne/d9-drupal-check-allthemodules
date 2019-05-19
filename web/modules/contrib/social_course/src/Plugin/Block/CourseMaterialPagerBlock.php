<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a 'CourseMaterialPagerBlock' block.
 *
 * @Block(
 *   id = "course_material_pager",
 *   admin_label = @Translation("Course material pager block"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseMaterialPagerBlock extends BlockBase {

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

      $title = $node->getTitle();
      $group_link = NULL;

      return [
        '#theme' => 'course_material_pager',
        '#title' => $title,
        '#node' => $node,
        '#section_class' => 'page-title',
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
