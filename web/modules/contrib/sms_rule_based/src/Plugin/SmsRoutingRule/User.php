<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\User.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Session\AccountInterface;
use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;
use Drupal\user\Entity\User as UserEntity;

/**
 * @SmsRoutingRule(
 *   id = "user",
 *   label = @Translation("SMS owner"),
 *   description = @Translation("The user that is sending the SMS message."),
 * );
 */
class User extends SmsRoutingRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    $users = $this->getOperand() ? UserEntity::loadMultiple(Tags::explode($this->getOperand())) : [];
    return [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => $users,
      '#tags' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processWidgetValue($form_value) {
    // The entity_autocomplete turns the value into an array, but what we need
    // is a string for storage.
    $form_value = array_reduce((array)$form_value, function ($result, $item) {
      if (isset($item['target_id'])) {
        $result[] = $item['target_id'];
      }
      return $result;
    }, []);
    return Tags::implode($form_value);
  }

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    return $this->satisfiesExpression($context['uid']) ? $numbers : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getReadableOperand() {
    $users = UserEntity::loadMultiple(Tags::explode($this->getOperand()));
    $user_names = array_reduce($users, function ($result, AccountInterface $user) {
      $result[] = $user->getDisplayName();
      return $result;
    }, []);
    return Tags::implode($user_names);
  }

}
