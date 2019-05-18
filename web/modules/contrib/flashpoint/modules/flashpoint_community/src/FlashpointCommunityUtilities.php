<?php
/**
 * @file
 */

namespace Drupal\flashpoint_community;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;

/**
 * Class FlashpointCommunityUtilities
 *
 * Provides some utilities functions commonly used in other modules.
 */
class FlashpointCommunityUtilities {

  /**
   * @param string $group_type
   *
   * Context may be "community" or "community"
   *
   * @return array $options
   */
  public static function getOptions($group_type = 'community') {
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_access');
    $plugin_definitions = $plugin_manager->getDefinitions();

    foreach ($plugin_definitions as $pd) {
      if (!isset($pd['group_type']) || $pd['group_type'] === $group_type)
      $options[$pd['id']] = $pd['label'];
    }
    return $options;
  }

  /**
   * @param GroupInterface $community
   * @param AccountInterface $account
   * @param string $return_type
   * @return array|bool
   */
  public static function joinAccess(GroupInterface $community, AccountInterface $account, $return_type = 'access') {
    // First check the person isn't a member already.
    if(!$community->getMember($account)) {
      $enroll_methods = [];
      $eval_plugins = [];
      $plugin_manager = \Drupal::service('plugin.manager.flashpoint_access');
      $plugin_definitions = $plugin_manager->getDefinitions();
      foreach (['field_enrollment_conditions_and', 'field_enrollment_conditions_or'] as $enroll_type) {
        if ($community->hasField($enroll_type)) {
          $enroll_methods[$enroll_type] = [];
          foreach ($community->get($enroll_type)->getValue() as $posn => $arr) {
            // Filter invalid entries
            if (in_array($arr['value'], array_keys($plugin_definitions))) {
              $enroll_methods[$enroll_type][$arr['value']] = FALSE;
              if (!in_array($arr['value'], $eval_plugins)) {
                $eval_plugins[] = $arr['value'];
              }
            }
          }
        }
        else {
          $enroll_methods[$enroll_type] = ['field_not_present' => TRUE];
        }
      }
      foreach ($eval_plugins as $ep) {
        if(method_exists($plugin_definitions[$ep]['class'], 'checkAccess')) {
          $access = $plugin_definitions[$ep]['class']::checkAccess($community, $account);
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
    // Either the method is 'access,' or something invalid
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
      case 'community':
        $join_text = t('Join community');
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