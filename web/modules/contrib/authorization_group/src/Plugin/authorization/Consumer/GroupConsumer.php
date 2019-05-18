<?php

namespace Drupal\authorization_group\Plugin\authorization\consumer;

use Drupal\authorization\Consumer\ConsumerPluginBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\user\UserInterface;

/**
 * Using Authorization to assign permissions to Groups.
 *
 * Groups are provided by the Group module.
 *
 * @AuthorizationConsumer(
 *   id = "authorization_group",
 *   label = @Translation("Groups"),
 *   description = @Translation("Assign users to Groups and Groups' roles")
 * )
 */
class GroupConsumer extends ConsumerPluginBase {

  protected $allowConsumerTargetCreation = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['delete_membership'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Remove the user from the Group, if they no longer have any roles'),
      '#default_value' => $this->configuration['delete_membership'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRowForm(array $form, FormStateInterface $form_state, $index) {
    $row = array();
    $mappings =  $this->configuration['profile']->getConsumerMappings();

    // Load all available groups, traverse through each of them, load all
    // available roles attached to that group, and assemble an array with the
    // information.
    // TODO: Should we have a filter on this?
    $groups = \Drupal::entityQuery('group')->execute();
    $group_options = ['none' => $this->t('- None -')];

    /** @var GroupInterface $group */
    foreach (Group::loadMultiple($groups) as $group_id => $group) {
      $group_name = Html::escape($group->label());
      /** @var GroupTypeInterface $group_type */
      $group_type = $group->getGroupType();

      /** @var GroupRoleInterface $role */
      foreach ($group_type->getRoles() as $role) {
        if (/*($role->isMember() || $role->isAnonymous()) && */ $role->isInternal()) {
          $group_options[$group_name][$group_id . '--' . $role->id()] = $role->label();
        }
      }
    }
    $row['group'] = array(
      '#type' => 'select',
      '#title' => t('Group and Role'),
      '#options' => $group_options,
      '#default_value' => $mappings[$index]['group'],
      '#description' => 'Choose the Group to apply to the user.',
    );
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function createConsumerTarget($consumer) {
    // TODO: Implement createConsumerTarget() method.
    // We are not currently allowing auto-recreation of AD groups in Drupal.
    // No work to do.
  }

  /**
   * {@inheritdoc}
   */
  public function getTokens() {
    $tokens = parent::getTokens();

    // Reset the tokens for plurality.
    $tokens['@' . $this->getType() . '_namePlural'] = $this->label();
    $tokens['@' . $this->getType() . '_name'] = 'Group';
    return $tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function grantSingleAuthorization(UserInterface $user, $consumer_mapping) {
    if (!empty($consumer_mapping) && $consumer_mapping != 'none') {

      list($group_id, $role_id) = explode('--', $consumer_mapping);
      $group = Group::load($group_id);
      $role = GroupRole::load($role_id);

      $group->addMember($user);

      $assign_new_role = TRUE;

      $membership_roles = [];

      // Go through all the roles that the user currently have, within the
      // group.
      // If the user isn't assigned to the role yet, the assign it. Otherwise,
      // leave the roles as they are.
      $memberships = $group->getContentByEntityId('group_membership', $user->id());
      if (!empty($memberships) && is_array($memberships) && $role) {
        /** @var GroupContent $membership */
        foreach ($memberships as $group_content_id => $membership) {
          $group_roles = $membership->get('group_roles');
          /** @var EntityReferenceItem $group_role */
          foreach ($group_roles as $index => $group_role) {
            $membership_roles[] = ['target_id' => $group_role->target_id];
            if ($group_role->target_id == $role_id) {
              $assign_new_role = FALSE;
            }
          }

          if ($assign_new_role) {
            $membership_roles[] = ['target_id' => $role_id];
            drupal_set_message($this->t('You have been granted the role %role in the %group group', [
              '%role' => $role->label(),
              '%group' => $group->label(),
            ]));
            $membership->set('group_roles', $membership_roles);
            // TODO: Handle potential exception here.
            $membership->save();
          }
        }
      }
    }
  }

  public function revokeGrants(UserInterface $user, array $context) {
    // TODO: Implement revokeGrants() method.
  }

  /**
   * {@inheritdoc}
   */
  public function revokeSingleAuthorization(&$user, $op, $incoming, $consumer_mapping, &$user_auth_data, $user_save = FALSE, $reset = FALSE) {
    // This method was in the original project, but is not actually defined
    // as part of the authorization interface.  Not sure how this is supposed
    // to be implemented, but leaving it for posterity.
    if (!empty($consumer_mapping) && $consumer_mapping['group'] != 'none') {

      $group_id = explode('--', $consumer_mapping['group'])[0];
      $role_id = explode('--', $consumer_mapping['group'])[1];
      $group = Group::load($group_id);
      $role = GroupRole::load($role_id);

      $roles_to_keep = [];

      // Go through all roles that the user has on this group, find the role
      // to remove, remove it and then save the users roles (membership) again.
      $memberships = $group->getContentByEntityId('group_membership', $user->id());
      if (!empty($memberships) && is_array($memberships)) {
        /** @var GroupContent $membership */
        foreach ($memberships as $group_content_id => $membership) {
          $group_roles = $membership->get('group_roles');
          /** @var EntityReferenceItem $group_role */
          foreach ($group_roles as $index => $group_role) {
            if ($group_role->target_id != $role_id) {
              $roles_to_keep[] = ['target_id' => $group_role->target_id];
            }
            else {
              drupal_set_message($this->t('Your %role role, in the %group group, has been revoked', [
                '%role' => $role->label(),
                '%group' => $group->label(),
              ]));
            }
          }
          $membership->set('group_roles', $roles_to_keep);
          $membership->save();
        }

        // If the user has not more roles in this group, remove membership.
        if (empty($roles_to_keep) && $this->configuration['delete_membership']) {
          $membership->delete();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitRowForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $consumer_mappings = array();
    foreach ($values as $key => $value) {
      $consumer_mappings[] = $value['consumer_mappings'];
    }

    $form_state->setValue('consumer_mappings', $consumer_mappings);

    parent::submitRowForm($form, $form_state);
  }
}
