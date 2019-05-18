<?php
/**
 * @file
 */

namespace Drupal\flashpoint;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;

/**
 * Class FlashpointUtilities
 *
 * Provides some utilities functions commonly used in other modules.
 */
class FlashpointUtilities {

  /**
   * @param string $context
   *
   * Context may be "course" or "community"
   *
   * @return array $options
   */
  public static function getOptions($context = 'course') {
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_access');
    $plugin_definitions = $plugin_manager->getDefinitions();

    foreach ($plugin_definitions as $pd) {
      if (!isset($pd['context']) || $pd['context'] === $context)
      $options[$pd['id']] = $pd['label'];
    }
    return $options;
  }

  public function groupJoinTitle(GroupInterface $group) {
    switch ($group->bundle()) {
      case 'course':
        return t('Enroll in course %label', ['%label' => $group->label()]);
        break;
      case 'community':
        return t('Join community %label', ['%label' => $group->label()]);
        break;
      default:
        return t('Join group %label', ['%label' => $group->label()]);
        break;
    }
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