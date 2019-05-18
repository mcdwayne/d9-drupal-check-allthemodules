<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserHasRole.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "user_has_role",
 *   label = @Translation("Logged user has role"),
 *   group = @Translation("User"),
 *   description = @Translation("Check if logged user has role."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class UserHasRole extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['roles'] = [
      '#type'          => 'checkboxes',
      '#title'         => t('Roles'),
      '#required'      => TRUE,
      '#options'       => $this->util->getUserRolesOptions(),
      '#default_value' => is_array($item->getSettings('roles')) ? $item->getSettings('roles') : [],
    ];

    $settings['criteria'] = [
      '#type'          => 'select',
      '#title'         => t('Match criteria'),
      '#description'   => t('The condition will check if current user has all selected roles or at least one role?'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('criteria'),
      '#options'       => [
        'all' => t('All roles'),
        'one' => t('At least one role'),
      ],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    foreach ($settings['roles'] as $key => $role) {
      if ($role === 0) {
        unset($settings['roles'][$key]);
      }
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $roles    = $condition->getSettings('roles');
    $criteria = $condition->getSettings('criteria');
    /** @var \Drupal\user\Entity\User $current_user */
    $current_user = $this->util->container->get('current_user');
    $user_roles   = array_values($current_user->getRoles());
    $result       = FALSE;

    if (is_array($roles) && is_array($user_roles)) {
      if ($criteria == 'all') {
        $result = (count(array_intersect($roles, $user_roles)) == count($roles));
      }
      elseif ($criteria == 'one') {
        $result = !empty(array_intersect($user_roles, $roles));
      }
    }

    return $result;
  }

}
