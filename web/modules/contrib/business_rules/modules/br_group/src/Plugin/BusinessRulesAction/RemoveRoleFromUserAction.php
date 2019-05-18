<?php

namespace Drupal\br_group\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\group\GroupMembership;
use Drupal\user\Entity\User;

/**
 * Class RemoveRoleFromUserAction.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "remove_role_from_user",
 *   label = @Translation("Group: Remove a role from an user"),
 *   group = @Translation("Group"),
 *   description = @Translation("Remove a role from an user on Group module."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class RemoveRoleFromUserAction extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['group_id'] = [
      '#type' => 'textfield',
      '#title' => t('Group Id'),
      '#required' => TRUE,
      '#description' => t('The group id. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('group_id'),
    ];

    $settings['role_id'] = [
      '#type' => 'textfield',
      '#title' => t('Role Machine name'),
      '#required' => TRUE,
      '#description' => t('The role machine name to remove from the user. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('role_id'),
    ];

    $settings['user_key'] = [
      '#type' => 'radios',
      '#title' => t('Key to select the user'),
      '#default_value' => $item->getSettings('user_key') ?: 'username',
      '#options' => [
        'username' => t('User Name'),
        'userid' => t('User Id'),
      ],
    ];

    $settings['user_name'] = [
      '#type' => 'textfield',
      '#title' => t('User Name'),
      '#description' => t('The user name. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('user_name'),
      '#states' => [
        'visible' => [
          ':input[name="user_key"]' => ['value' => 'username'],
        ],
      ],
    ];

    $settings['user_id'] = [
      '#type' => 'textfield',
      '#title' => t('User Id'),
      '#description' => t('The user id. You may use variable or token to fill this information'),
      '#default_value' => $item->getSettings('user_id'),
      '#states' => [
        'visible' => [
          ':input[name="user_key"]' => ['value' => 'userid'],
        ],
      ],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('user_key') == 'username' && $form_state->getValue('user_name') == '') {
      $form_state->setErrorByName('user_name', t('User name is required.'));
    }
    elseif ($form_state->getValue('user_key') == 'userid' && $form_state->getValue('user_id') == '') {
      $form_state->setErrorByName('user_id', t('User id is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    if ($settings['user_key'] == 'username') {
      $settings['user_id'] = NULL;
    }
    elseif ($settings['user_key'] == 'userid') {
      $settings['user_name'] = NULL;
    }

    return parent::processSettings($settings, $item);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $variables = $event->getArgument('variables');
    $group_id = $action->getSettings('group_id');
    $group_id = $this->processVariables($group_id, $variables);
    $role_id = $action->getSettings('role_id');
    $role_id = $this->processVariables($role_id, $variables);
    $user_key = $action->getSettings('user_key');
    $user_name = $action->getSettings('user_name');
    $user_name = $this->processVariables($user_name, $variables);
    $user_id = $action->getSettings('user_id');
    $user_id = $this->processVariables($user_id, $variables);

    if ($user_key == 'userid') {
      $user = User::load($user_id);
    }
    else {
      $id = $this->util->container->get('entity_type.manager')
        ->getStorage('user')
        ->getQuery()
        ->condition('name', $user_name)
        ->execute();

      $user = User::load(array_values($id)[0]);
    }

    $group = Group::load($group_id);
    $member = $group->getMember($user);
    if ($member instanceof GroupMembership) {
      $roles = $member->getRoles();
      $group_type_id = $group->getGroupType()->id();
      $role_id = substr($role_id, 0, strlen("$group_type_id-")) == "$group_type_id-" ? $role_id : "$group_type_id-$role_id";
      $role_to_remove = GroupRole::load($role_id);
      $user_has_role = in_array($role_id, array_keys($roles)) ? TRUE : FALSE;
      unset($roles["$group_type_id-member"]);
      unset($roles[$role_id]);
      $roles_keys = array_keys($roles);

      foreach ($roles_keys as $key => $value) {
        $roles_keys[$key] = ['target_id' => $value];
      }

      if ($user_has_role) {
        $content = $member->getGroupContent();
        $content->set('group_roles', $roles_keys);
        $content->save();

        $result = [
          '#type' => 'markup',
          '#markup' => t('Role %role has been removed from user %user on group %group.', [
            '%role' => $role_to_remove->label(),
            '%user' => $user->getUsername(),
            '%group' => $group->label(),
          ]),
        ];
      }
      else {
        $result = [
          '#type' => 'markup',
          '#markup' => t('User %user does not have role %role on group %group.', [
            '%role' => $role_id,
            '%user' => $user->getUsername(),
            '%group' => $group->label(),
          ]),
        ];
      }
    }
    else {
      $result = [
        '#type' => 'markup',
        '#markup' => t('Role %role could not be removed from user %user because he is not member of group %group.', [
          '%role' => $role_id,
          '%user' => $user->getUsername(),
          '%group' => $group->label(),
        ]),
      ];
    }

    return $result;
  }

}
