<?php

namespace Drupal\br_group\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\GroupMembership;
use Drupal\user\Entity\User;

/**
 * Class GroupHasUserCondition.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "group_has_user",
 *   label = @Translation("Check if an user is in a group"),
 *   group = @Translation("Group"),
 *   description = @Translation("Check if an user is member of a group."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class GroupHasUserCondition extends BusinessRulesConditionPlugin {

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
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $variables = $event->getArgument('variables');
    $group_id = $condition->getSettings('group_id');
    $group_id = $this->processVariables($group_id, $variables);
    $user_key = $condition->getSettings('user_key');
    $user_name = $condition->getSettings('user_name');
    $user_name = $this->processVariables($user_name, $variables);
    $user_id = $condition->getSettings('user_id');
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
      $result = TRUE;
    }
    else {
      $result = FALSE;
    }

    return $result;
  }

}
