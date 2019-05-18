<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Class UserVariableHasRole.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "user_variable_has_role",
 *   label = @Translation("User variable has role"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Check if variable user has role."),
 * )
 */
class UserVariableHasRole extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['user_variable'] = [
      '#type'          => 'select',
      '#required'      => TRUE,
      '#title'         => t('User variable'),
      '#description'   => t('Select the variable that represents the user.'),
      '#options'       => $this->util->getVariablesOptions([
        'user_variable',
        'entity_empty_variable',
      ], ['user'], ['user']),
      '#default_value' => $item->getSettings('user_variable'),
    ];

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
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
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
  public function getVariables(ItemInterface $item) {
    $variable_id    = $item->getSettings('user_variable');
    $variable       = Variable::load($variable_id);
    $variableSet    = new VariablesSet();
    $variableObject = new VariableObject($variable_id, NULL, $variable->getType());
    $variableSet->append($variableObject);

    return $variableSet;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $roles            = $condition->getSettings('roles');
    $criteria         = $condition->getSettings('criteria');
    $user_variable_id = $condition->getSettings('user_variable');
    /** @var \Drupal\business_rules\VariablesSet $event_variables */
    $event_variables = $event->getArgument('variables');
    /** @var \Drupal\user\Entity\User $user_variable */
    $user_variable = $event_variables->getVariable($user_variable_id)
      ->getValue();

    // Check if $user_variable is defined.
    if (!$user_variable instanceof User) {
      $this->util->logger->error('Trying to check user roles in a null user object. Condition: %condition', [
        '%condition' => $condition->label() . ' [' . $condition->id() . ']',
      ]);

      return FALSE;
    }

    $user_roles = array_values($user_variable->getRoles());
    $result     = FALSE;

    if ($criteria == 'all') {
      $result = (count(array_intersect($roles, $user_roles)) == count($roles));
    }
    elseif ($criteria == 'one') {
      $result = !empty(array_intersect($user_roles, $roles));
    }

    return $result;
  }

}
