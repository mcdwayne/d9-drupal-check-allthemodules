<?php

namespace Drupal\Tests\subscription\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\subscription_entity\Entity\subscription_term;

/**
 * Defines an abstract test base for subscription kernel tests.
 */
abstract class SubscriptionKernelTestBase extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'config',
    'taxonomy',
    'user',
    'views',
    'ctools',
    'field_group',
    'inline_entity_form',
    'system',
    'field',
    'text',
    'filter',
    'datetime',
    'entity_test',
    'subscription',
  ];

  protected $subscriptionTypeKey;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;
  public $subscriptionTypeEntity;
  public $subscriptionTermTypeEntity;
  public $subscriptionRole;
  public $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->subscriptionTypeKey = $this->randomMachineName();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->installEntitySchema('subscription_type');
    $this->installEntitySchema('subscription');
    $this->installEntitySchema('subscription_term');

    $this->createRole([
      'id' => 'authenticated_user',
      'label' => 'Authenticated user',
    ]);
    $role = $this->createRole([
      'id' => 'premium',
      'label' => 'Premium',
    ]);
    $this->subscriptionRole = $role;

    $this->account = $this->createUser();

    $this->subscriptionTypeEntity = $this->createSubscriptionType();
    $this->subscriptionTermTypeEntity = $this->createSubscriptionTermType();

  }

  /**
   * Set the current user so subscription creation can rely on it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to set as the current user.
   */
  protected function setCurrentUser(AccountInterface $account) {
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Creates a drupal role.
   *
   * @param array $info
   *   Array of info to create the role.
   *
   * @return \Drupal\user\Entity\Role
   *   A role object.
   */
  protected function createRole(array $info) {
    $role = Role::create($info);
    $role->save();
    return $role;
  }

  /**
   * Gives us the role assinged to the subscription.
   *
   * @return \Drupal\user\Entity\Role
   *   A role object.
   */
  protected function getSubscriptionRole() {
    return $this->subscriptionRole;
  }

  /**
   * Creates a subscription type.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionType
   *   The created subscription type entity.
   */
  protected function createSubscriptionType() {
    $subscriptionType = $this->entityTypeManager->getStorage('subscription_type')->create([
      'id' => $this->subscriptionTypeKey,
      'role' => $this->getSubscriptionRole()->id(),
    ]);
    $subscriptionType->save();
    return $subscriptionType;
  }

  /**
   * Creates a subscription term type.
   *
   * @return \Drupal\subscription_entity\Entity\SubscriptionTermType
   *   The created subscription type entity.
   */
  protected function createSubscriptionTermType() {
    $subscriptionTermType = $this->entityTypeManager->getStorage('subscription_term_type')->create([
      'id' => $this->subscriptionTypeKey,
    ]);
    $subscriptionTermType->save();
    return $subscriptionTermType;
  }

  /**
   * Creates a subscription.
   *
   * @return \Drupal\subscription_entity\Entity\subscriptionType
   *   The created subscription type entity.
   */
  protected function createSubscription() {
    $subscription = $this->entityTypeManager->getStorage('subscription')->create([
      'type' => $this->subscriptionTypeKey,
      'subscription_owner_uid' => ['target_id' => $this->account->id()],
    ]);
    $subscription->save();
    return $subscription;
  }

  /**
   * Creates a subscription term.
   *
   * @return \Drupal\subscription_entity\Entity\subscription_term
   *   The created subscription term entity.
   */
  protected function createSubscriptionTerm($subscription, $startDate = '2017-01-01', $state = SUBSCRIPTION_ACTIVE) {

    $subscriptionTerm = subscription_term::create([
      'type' => $this->subscriptionTypeKey,
      'term_status' => $state,
      'timezone' => 'UTC',
      'start_date' => $startDate,
      'user_id' => 1,
      'subscription_entity_id' => [
        'target_id' => $subscription->id(),
      ],
    ]);

    $subscriptionTerm->save();

    return $subscriptionTerm;
  }

}
