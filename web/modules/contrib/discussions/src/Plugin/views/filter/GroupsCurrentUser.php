<?php

namespace Drupal\discussions\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\views\Plugin\views\filter\Equality;

/**
 * Filters groups by current user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("groups_current_user")
 */
class GroupsCurrentUser extends Equality {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Get IDs of all groups the current user is a member of.
    $user = \Drupal::currentUser();
    $groups = Group::loadMultiple();

    $user_groups = [-1];

    /** @var \Drupal\group\Entity\Group $group */
    foreach ($groups as $group) {
      $group_member = $group->getMember($user);
      if (!empty($group_member)) {
        $user_groups[] = $group->id();
      }
    }

    // Add group ID condition.
    if (!empty($user_groups)) {
      $where_group = 0;
      $in_operator = ($this->operator == '=') ? 'IN' : 'NOT IN';
      $this->query->addWhere($where_group,
        'group_content_field_data_discussions.gid', $user_groups, $in_operator);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#markup' => t('Current user'),
    ];
  }

}
