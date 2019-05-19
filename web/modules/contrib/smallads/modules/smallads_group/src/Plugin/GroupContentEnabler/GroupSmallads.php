<?php

namespace Drupal\smallads_group\Plugin\GroupContentEnabler;

use Drupal\smallads\Entity\SmalladType;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a content enabler for transactions.
 *
 * @GroupContentEnabler(
 *   id = "smallad",
 *   label = @Translation("Smallad"),
 *   description = @Translation("Adds smallads to groups."),
 *   entity_type_id = "smallad",
 *   pretty_path_key = "smallad",
 *   enforced = FALSE,
 *   deriver = "Drupal\smallads_group\Plugin\GroupContentEnabler\SmalladDeriver"
 * )
 */
class GroupSmallads extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['group_cardinality'] = 1;
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $type = $this->getEntityBundle();
    $operations = [];
    if ($group->hasPermission('create-edit-delete own smallads', \Drupal::currentUser())) {
      $operations["create-$type"] = [
        'title' => $this->t('Post new @type', ['@type' => $this->getSmalladType()->label()]),
        'url' => new Url(
          'entity.smallad.add_form',
          ['group' => $group->id(), 'smallad_type' => $this->getEntityBundle()]
        ),
        'weight' => 5,
      ];
    }
    return $operations;
  }
  /**
   * {@inheritdoc}
   *
   * @note this will return identical permissions for every smallad type
   */
  public function getPermissions() {
    $permissions['create-edit-delete own smallads']['title'] = 'Post smallads';
    $permissions['manage-smallads']['title'] = 'Manage smallads';
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'create-edit-delete own smallads');
  }

  /**
   * {@inheritdoc}
   */
  protected function viewAccess(GroupContentInterface $group_content, AccountInterface $account) {
    return GroupAccessResult::allowedIfHasGroupPermission(
      $group_content->getGroup(),
      $account,
      'view group_membership content'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function updateAccess(GroupContentInterface $group_content, AccountInterface $account) {
    return $this->edit_delete_access($group_content, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    return $this->edit_delete_access($group_content, $account);
  }




  private function edit_delete_access(GroupContentInterface $group_content, AccountInterface $account) {
    $group = $group_content->getGroup();

    // Allow members to edit their own smallads.
    if ($group_content->entity_id->entity->id() == $account->id()) {
      return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'create-edit-delete own smallads');
    }

    return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'manage-smallads');
  }

  /**
   * Retrieves the node type this plugin supports.
   *
   * @return \Drupal\node\NodeTypeInterface
   *   The node type this plugin supports.
   */
  private function getSmalladType() {
    return SmalladType::load($this->getEntityBundle());
  }

}
