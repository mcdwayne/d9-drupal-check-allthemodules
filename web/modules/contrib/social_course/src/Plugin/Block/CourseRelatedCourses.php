<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class CourseRelatedCourses.
 *
 * @Block(
 *   id = "related_courses",
 *   admin_label = @Translation("Related courses"),
 *   context = {
 *     "group" = @ContextDefinition("entity:group")
 *   }
 * )
 */
class CourseRelatedCourses extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $ids = [];
    $items = [];
    $group = $this->getContextValue('group');

    foreach ($group->get('field_course_related_courses')->getValue() as $field_value) {
      $ids[] = $field_value['target_id'];
    }

    $storage = \Drupal::entityTypeManager()->getStorage('group');
    $related_courses = $storage->loadMultiple($ids);
    foreach ($related_courses as $related_course) {
      $items[] = [
        'label' => $related_course->label(),
        'url' => $related_course->toUrl(),
      ];
    }

    return [
      '#theme' => 'course_related_courses',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $group = $this->getContextValue('group');
    if (!empty($group->get('field_course_related_courses')->getValue())) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
