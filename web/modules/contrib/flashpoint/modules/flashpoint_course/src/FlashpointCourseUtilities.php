<?php
/**
 * @file
 */

namespace Drupal\flashpoint_course;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;

/**
 * Class FlashpointCourseUtilities
 *
 * Provides some utilities functions commonly used in other modules.
 */
class FlashpointCourseUtilities {

  /**
   * @param string $group_type
   *
   * Group type may be "course" or "community"
   *
   * @return array $options
   */
  public static function getOptions($group_type = 'course') {

    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_access');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $options = [];
    foreach ($plugin_definitions as $pd) {
      if (!isset($pd['group_type']) || $pd['group_type'] === $group_type) {
        $options[$pd['id']] = $pd['label'];
      }

    }
    return $options;
  }

  /**
   * @param $group
   * @param null $account
   * @return bool
   *   Whether the account is a enrolled in the course.
   */
  public static function userIsEnrolled($group, $account = NULL) {
    if (is_numeric($group)) {
      $group = Group::load($group);
    }
    $account = $account ? $account : \Drupal::currentUser();
    return $group->getMember($account) ? TRUE : FALSE;
  }

  /**
   * @param GroupInterface $group
   * @return bool
   */
  public static function isOpenAccessCourse(GroupInterface $group) {
    $open = $group->get('field_open_access_course')->getValue();
    if (isset($open[0]['value']) && $open[0]['value']) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public static function trackCourseProgress(GroupInterface $group) {
    $track = $group->get('field_track_course_progress')->getValue();
    if (isset($track[0]['value']) && $track[0]['value']) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param GroupInterface $course
   * @param AccountInterface $account
   * @param string $return_type
   * @return array|bool
   */
  public static function enrollAccess(GroupInterface $course, AccountInterface $account, $return_type = 'access') {
    // First check the person isn't a member already.
    if(!$course->getMember($account)) {
      $enroll_methods = [];
      $eval_plugins = [];
      $plugin_manager = \Drupal::service('plugin.manager.flashpoint_access');
      $plugin_definitions = $plugin_manager->getDefinitions();
      foreach (['field_enrollment_conditions_and', 'field_enrollment_conditions_or'] as $enroll_type) {
        $enroll_methods[$enroll_type] = [];
        foreach ($course->get($enroll_type)->getValue() as $posn => $arr) {
          // Filter invalid entries
          if (in_array($arr['value'], array_keys($plugin_definitions))) {
            $enroll_methods[$enroll_type][$arr['value']] = FALSE;
            if (!in_array($arr['value'], $eval_plugins)) {
              $eval_plugins[] = $arr['value'];
            }
          }
        }
      }
      foreach ($eval_plugins as $ep) {
        if(method_exists($plugin_definitions[$ep]['class'], 'checkAccess')) {
          $access = $plugin_definitions[$ep]['class']::checkAccess($course, $account);
          foreach ($enroll_methods as $enroll_type => $list) {
            if(key_exists($ep, $list)) {
              $enroll_methods[$enroll_type][$ep] = $access;
            }
          }
        }
      }
      if((!in_array(FALSE, $enroll_methods['field_enrollment_conditions_and'])
          || empty($enroll_methods['field_enrollment_conditions_and']))
        && (in_array(TRUE, $enroll_methods['field_enrollment_conditions_or'])
          || empty($enroll_methods['field_enrollment_conditions_or']))) {
        if ($return_type === 'access') {
          return TRUE;
        }
      }
      if ($return_type === 'status') {
        return $enroll_methods;
      }
    }
    // Either the method is 'access', or something invalid
    return FALSE;
  }

  /**
   * Provides the form for joining a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return array
   *   A group join form.
   */
  public function groupJoinForm(GroupInterface $group) {
    $join_text = t('Join group');
    switch ($group->bundle()) {
      case 'course':
        $join_text = t('Enroll');
        break;
      default:
        $join_text = t('Join group');
        break;
    }
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    // Pre-populate a group membership with the current user.
    $group_content = GroupContent::create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => \Drupal::currentUser()->id(),
    ]);

    $form = \Drupal::service('entity.form_builder')->getForm($group_content, 'group-join');
    $form['actions']['submit']['#value'] = $join_text;
    return $form;
  }
}