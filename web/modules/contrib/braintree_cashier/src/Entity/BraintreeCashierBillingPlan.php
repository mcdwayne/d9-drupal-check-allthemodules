<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Braintree Cashier billing plan entity.
 *
 * @ingroup braintree_cashier
 *
 * @ContentEntityType(
 *   id = "braintree_cashier_billing_plan",
 *   label = @Translation("Billing plan"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\braintree_cashier\BraintreeCashierBillingPlanListBuilder",
 *     "views_data" = "Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanViewsData",
 *     "translation" = "Drupal\braintree_cashier\BraintreeCashierBillingPlanTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\braintree_cashier\Form\BraintreeCashierBillingPlanForm",
 *       "add" = "Drupal\braintree_cashier\Form\BraintreeCashierBillingPlanForm",
 *       "edit" = "Drupal\braintree_cashier\Form\BraintreeCashierBillingPlanForm",
 *       "delete" = "Drupal\braintree_cashier\Form\BraintreeCashierBillingPlanDeleteForm",
 *     },
 *     "access" = "Drupal\braintree_cashier\BraintreeCashierBillingPlanAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\braintree_cashier\BraintreeCashierBillingPlanHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "braintree_cashier_billing_plan",
 *   data_table = "braintree_cashier_billing_plan_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer braintree cashier billing plan entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "is_available_for_purchase",
 *   },
 *   links = {
 *     "canonical" = "/admin/braintree-cashier/braintree_cashier_billing_plan/{braintree_cashier_billing_plan}",
 *     "add-form" = "/admin/braintree-cashier/braintree_cashier_billing_plan/add",
 *     "edit-form" = "/admin/braintree-cashier/braintree_cashier_billing_plan/{braintree_cashier_billing_plan}/edit",
 *     "delete-form" = "/admin/braintree-cashier/braintree_cashier_billing_plan/{braintree_cashier_billing_plan}/delete",
 *     "collection" = "/admin/braintree-cashier/braintree_cashier_billing_plans",
 *   },
 *   field_ui_base_route = "braintree_cashier_billing_plan.settings",
 *   constraints = {
 *     "BraintreeCashierBillingPlanEnvironment" = {},
 *   }
 * )
 */
class BraintreeCashierBillingPlan extends ContentEntityBase implements BraintreeCashierBillingPlanInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName() . ' (' . $this->getEnvironment() . ')';
  }

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
  public function getEnvironment() {
    return $this->get('environment')->value;
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
  public function isAvailableForPurchase() {
    return $this->get('is_available_for_purchase')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFreeTrial() {
    return $this->get('has_free_trial')->value;
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
  public function setIsAvailableForPurchase($is_available_for_purchase) {
    $this->set('status', $is_available_for_purchase ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongDescription() {
    $text = $this->get('long_description')->value;
    $format = $this->get('long_description')->format;
    return check_markup($text, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    $text = $this->get('price')->value;
    $format = $this->get('price')->format;
    return check_markup($text, $format);
  }

  /**
   * {@inheritdoc}
   */
  public function getCallToAction() {
    return $this->get('call_to_action')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBraintreePlanId() {
    return $this->get('braintree_plan_id')->value;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Billing plan entity.'))
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
        'weight' => 5,
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
      ->setDescription(t('The name of the Billing plan entity. It is shown to the user on the My Subscription tab of their account.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
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

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Shown in the drop-down select on the checkout page. Example: <em>Monthly plan for $9.99 / month.</em>'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'weight' => -4,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['braintree_plan_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Braintree Plan ID'))
      ->setDescription(t('The ID for the plan entered on the <a href="@dashboard">Braintree control panel</a>', [
        '@dashboard' => 'https://sandbox.braintreegateway.com',
      ]))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this billing plan in relation to others in the same environment. Higher weights sink lower in the plan selection list presented on /signup'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'weight' => 0,
      ]);

    $fields['subscription_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Subscription type to create.'))
      ->setSetting('allowed_values_function', 'braintree_cashier_billing_plan_subscription_type_options')
      ->setRequired(TRUE)
      ->setDefaultValue(BraintreeCashierSubscriptionInterface::PAID_INDIVIDUAL)
      ->setDescription(t('Subscriptions created from this billing plan will be of this type.'))
      ->setDisplayOptions('form', [
        'weight' => -2,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_available_for_purchase'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is available for purchase'))
      ->setDescription(t('A boolean indicating whether the Billing plan is available for purchase.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'weight' => 10,
      ]);

    $fields['has_free_trial'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Has free trial'))
      ->setDescription(t('A boolean indicating whether this Billing Plan has a free trial. Setting this prevents users from getting multiple free trials since it causes the Braintree Cashier module to override the Braintree Console settings based on the "Had free trial" boolean on the user entity. The Billing Plan in the Braintree Console needs to be properly configured for free trials.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'weight' => 10,
      ]);

    $fields['roles_to_assign'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Role(s) to assign'))
      ->setDescription(t('Role(s) to assign when a subscription with this Billing Plan becomes <em>active</em>. Changes here have no effect on existing subscriptions.'))
      ->setSettings([
        'allowed_values_function' => 'braintree_cashier_get_role_options',
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 0,
      ]);

    $fields['roles_to_revoke'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Role(s) to revoke'))
      ->setDescription(t('Role(s) to revoke when a subscription with this Billing Plan becomes <em>canceled</em>. Changes here have no effect on existing subscriptions.'))
      ->setSettings([
        'allowed_values_function' => 'braintree_cashier_get_role_options',
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 0,
      ]);

    $fields['environment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Environment'))
      ->setDescription(t('The Braintree environment for this billing plan.'))
      ->setSetting('allowed_values', [
        BraintreeApiService::ENVIRONMENT_PRODUCTION => t('Production'),
        BraintreeApiService::ENVIRONMENT_SANDBOX => t('Sandbox'),
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'weight' => -2,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['price'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Price'))
      ->setDescription(t('For display on the plans overview page. For example <em>$12</em>.'))
      ->setDisplayOptions('form', [
        'weight' => -3,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['long_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Long description'))
      ->setDescription(t('The long description displayed on the plans overview page.'))
      ->setDisplayOptions('form', [
        'weight' => -3,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['call_to_action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Call to action'))
      ->setDescription(t('The text to display on the call to action on the plans overview page. For example <em>Start Learning</em>.'))
      ->setDisplayOptions('form', [
        'weight' => -3,
      ])
      ->setDisplayOptions('view', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
