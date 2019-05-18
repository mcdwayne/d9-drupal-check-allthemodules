<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Braintree Cashier subscription entity.
 *
 * @ingroup braintree_cashier
 *
 * @ContentEntityType(
 *   id = "braintree_cashier_subscription",
 *   label = @Translation("Subscription"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\braintree_cashier\BraintreeCashierSubscriptionListBuilder",
 *     "views_data" = "Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\braintree_cashier\Form\BraintreeCashierSubscriptionForm",
 *       "add" = "Drupal\braintree_cashier\Form\BraintreeCashierSubscriptionForm",
 *       "edit" = "Drupal\braintree_cashier\Form\BraintreeCashierSubscriptionForm",
 *       "delete" = "Drupal\braintree_cashier\Form\BraintreeCashierSubscriptionDeleteForm",
 *     },
 *     "access" = "Drupal\braintree_cashier\BraintreeCashierSubscriptionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\braintree_cashier\BraintreeCashierSubscriptionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "braintree_cashier_subscription",
 *   admin_permission = "administer braintree cashier subscription entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/braintree-cashier/braintree_cashier_subscription/{braintree_cashier_subscription}",
 *     "add-form" = "/admin/braintree-cashier/braintree_cashier_subscription/add",
 *     "edit-form" = "/admin/braintree-cashier/braintree_cashier_subscription/{braintree_cashier_subscription}/edit",
 *     "delete-form" = "/admin/braintree-cashier/braintree_cashier_subscription/{braintree_cashier_subscription}/delete",
 *     "collection" = "/admin/braintree-cashier/braintree_cashier_subscriptions",
 *   },
 *   field_ui_base_route = "braintree_cashier_subscription.settings",
 *   constraints = {
 *     "BraintreeCashierPeriodEndDateNotEmpty" = {},
 *     "BraintreeCashierBraintreeSubscriptionId" = {},
 *     "OneActiveBraintreeCashierSubscription" = {},
 *   }
 * )
 */
class BraintreeCashierSubscription extends ContentEntityBase implements BraintreeCashierSubscriptionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingPlan() {
    return $this->get('billing_plan')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setBraintreeSubscriptionId($braintree_subscription_id) {
    $this->set('braintree_subscription_id', $braintree_subscription_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPeriodEndDate($timestamp) {
    $end_date = DrupalDateTime::createFromTimestamp($timestamp, DateTimeItemInterface::STORAGE_TIMEZONE);
    $this->{'period_end_date'}->value = $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function willCancelAtPeriodEnd() {
    return $this->get('cancel_at_period_end')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelAtPeriodEnd($will_cancel) {
    $this->set('cancel_at_period_end', $will_cancel);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBraintreeSubscriptionId() {
    return $this->get('braintree_subscription_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionType() {
    return $this->get('subscription_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriodEndDate() {
    $end_date = new \DateTime($this->get('period_end_date')->value);
    return $end_date->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getTrialStartDate() {
    return $this->get('trial_start_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrialStartDate($timestamp) {
    $this->set('trial_start_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function beganWithFreeTrial() {
    return (bool) $this->get('trial_start_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrialEndDate() {
    return $this->get('trial_end_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrialEndDate($timestamp) {
    $this->set('trial_end_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndedAtDate() {
    return $this->get('ended_at_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndedAtDate($timestamp) {
    $this->set('ended_at_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanceledAtDate() {
    return $this->get('canceled_at_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCanceledAtDate($timestamp) {
    $this->set('canceled_at_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function periodEndDateIsSet() {
    return (bool) $this->get('period_end_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesToAssign() {
    $roles = [];
    foreach ($this->get('roles_to_assign') as $roleItem) {
      $roles[] = $roleItem->value;
    }
    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesToRevoke() {
    $roles = [];
    foreach ($this->get('roles_to_revoke') as $roleItem) {
      $roles[] = $roleItem->value;
    }
    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribedUser() {
    return $this->get('subscribed_user')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribedUserId() {
    return $this->get('subscribed_user')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('subscription_type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingPlan($billing_plan_id) {
    $this->set('billing_plan', $billing_plan_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRolesToAssign(array $roles_to_assign) {
    $this->set('roles_to_assign', $roles_to_assign);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRolesToRevoke(array $roles_to_revoke) {
    $this->set('roles_to_revoke', $roles_to_revoke);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscounts() {
    return $this->get('discount');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Subscription entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the subscription which is displayed to the user on their profile. It is copied from the name of the billing plan from which the subscription was created.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subscribed_user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subscribed user'))
      ->setDescription(t('The user account which will have this subscription.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_plan'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing plan'))
      ->setDescription(t('The billing plan from which this subscription was generated, if applicable.'))
      ->setSetting('target_type', 'braintree_cashier_billing_plan')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'weight' => 10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Discount'))
      ->setDescription(t('Discounts associated with this subscription'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'braintree_cashier_discount')
      ->setDisplayOptions('view', [
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['subscription_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Subscription type'))
      ->setSetting('allowed_values_function', 'braintree_cashier_get_subscription_type_options')
      ->setRequired(TRUE)
      ->setDefaultValue(self::FREE)
      ->setDescription(t('Subscription type. Normally a site administrator will only create a subscription of type <em>free</em>. A user with an <em>Enterprise Manager</em> subscription may add subscriptions of type <em>Enterprise Individual</em> to other users.'))
      ->setDisplayOptions('form', [
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 0,
      ]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Subscription status'))
      ->setDescription(t('The current subscription status. If this subscription is for a paying subscriber, then normally a site administrator would change this to canceled only if a refund was given to the user in the Braintree control panel.'))
      ->setSetting('allowed_values', [
        self::ACTIVE => t('Active'),
        self::CANCELED => t('Canceled'),
      ])
      ->setDisplayOptions('form', [
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(self::ACTIVE);

    $fields['cancel_at_period_end'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cancel at period end'))
      ->setDescription(t('A boolean indicating whether to cancel the subscription at period end. If this is false, then this subscription will not automatically cancel at the period end date.'))
      ->setDisplayOptions('form', [
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(FALSE);

    $fields['is_trialing'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is trialing'))
      ->setDescription(t('The subscription is currently on a free trial managed by Braintree'))
      ->setDisplayOptions('form', [
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDefaultValue(FALSE);

    $fields['period_end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Period end date'))
      ->setDescription(t('The end date of the current subscription period. Subscriptions can still legitimately be active past this date depending on your Braintree payment retry logic in case a payment has failed.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'weight' => 1,
      ]);

    $fields['trial_start_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Free trial start date'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 1,
      ]);

    $fields['trial_end_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Free trial end date'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 1,
      ]);

    $fields['ended_at_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Subscription end date'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 1,
      ]);

    $fields['canceled_at_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Subscription canceled at date'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 1,
      ]);

    $fields['braintree_subscription_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Braintree subscription ID'))
      ->setDescription(t('The subscription ID reported by the Braintree API'))
      ->setDisplayOptions('view', [
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'weight' => 5,
      ]);

    $fields['roles_to_assign'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Role(s) to assign'))
      ->setDescription(t('Role(s) to assign when this subscription becomes <em>active</em>.'))
      ->setSettings([
        'allowed_values_function' => 'braintree_cashier_get_role_options',
      ])
      ->setDisplayOptions('view', [
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 2,
      ]);

    $fields['roles_to_revoke'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Role(s) to revoke'))
      ->setDescription(t('Role(s) to revoke when this subscription becomes <em>canceled</em>.'))
      ->setSettings([
        'allowed_values_function' => 'braintree_cashier_get_role_options',
      ])
      ->setDisplayOptions('view', [
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 2,
      ]);

    $fields['cancel_message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Cancel message'))
      ->setDescription(t('The reason the user has given for canceling.'))
      ->setDisplayOptions('form', [
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'weight' => 10,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setCancelMessage($message) {
    $this->set('cancel_message', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Assign roles to the user when a new subscription is saved.
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (!$update) {
      $user = $this->getSubscribedUser();
      if (!empty($user)) {
        // Assign roles if the subscription is active.
        if ($this->getStatus() == self::ACTIVE) {
          foreach ($this->getRolesToAssign() as $role) {
            $user->addRole($role);
          }
        }
        $user->save();
      }
    }
    // Invalidate the "Subscription" tab local tasks cache.
    $theme_machine_name = \Drupal::theme()->getActiveTheme()->getName();
    Cache::invalidateTags([
      'user:' . $this->getSubscribedUserId(),
      'config:block.block.' . $theme_machine_name . '_local_tasks',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    foreach ($entities as $entity) {
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $entity */
      Cache::invalidateTags(['user:' . $entity->getSubscribedUserId()]);
    }
    $theme_machine_name = \Drupal::theme()->getActiveTheme()->getName();
    Cache::invalidateTags(['config:block.block.' . $theme_machine_name . '_local_tasks']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscriptionTypesNeedBraintreeId() {
    $types[] = self::PAID_INDIVIDUAL;
    \Drupal::moduleHandler()->alter('braintree_cashier_subscription_types_need_braintree_id', $types);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function isTrialing() {
    return $this->get('is_trialing')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsTrialing($is_trialing) {
    $this->set('is_trialing', $is_trialing);
    return $this;
  }

}
