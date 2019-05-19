<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\subscription_entity\Event\SubscriptionStateUpdatedEvent;

/**
 * Defines the Subscription entity.
 *
 * @ingroup subscription
 *
 * @ContentEntityType(
 *   id = "subscription",
 *   label = @Translation("Subscription"),
 *   bundle_label = @Translation("Subscription type"),
 *   handlers = {
 *     "storage" = "Drupal\subscription_entity\SubscriptionStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\subscription_entity\SubscriptionListBuilder",
 *     "views_data" = "Drupal\subscription_entity\Entity\SubscriptionViewsData",
 *     "translation" = "Drupal\subscription_entity\SubscriptionTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\subscription_entity\Form\SubscriptionForm",
 *       "add" = "Drupal\subscription_entity\Form\SubscriptionForm",
 *       "edit" = "Drupal\subscription_entity\Form\SubscriptionForm",
 *       "delete" = "Drupal\subscription_entity\Form\SubscriptionDeleteForm",
 *     },
 *     "access" = "Drupal\subscription_entity\SubscriptionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\subscription_entity\SubscriptionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "subscription",
 *   data_table = "subscription_field_data",
 *   revision_table = "subscription_revision",
 *   revision_data_table = "subscription_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer subscription entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "subscription_ref",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/subscription/subscription/{subscription}",
 *     "add-page" = "/subscription/subscription/add",
 *     "add-form" = "/subscription/subscription/add/{subscription_type}",
 *     "edit-form" = "/subscription/subscription/{subscription}/edit",
 *     "delete-form" = "/subscription/subscription/{subscription}/delete",
 *     "version-history" = "/subscription/subscription/{subscription}/revisions",
 *     "revision" = "/subscription/subscription/{subscription}/revisions/{subscription_revision}/view",
 *     "revision_delete" = "/subscription/subscription/{subscription}/revisions/{subscription_revision}/delete",
 *     "collection" = "/subscription/subscription",
 *   },
 *   bundle_entity_type = "subscription_type",
 *   field_ui_base_route = "entity.subscription_type.edit_form"
 * )
 */
class Subscription extends RevisionableContentEntityBase implements SubscriptionInterface {

  use EntityChangedTrait;

  /**
   * Gets the subscription loader.
   */
  protected function subscriptionLoader() {
    return \Drupal::service('subscription_entity.subscription_entity_loader');
  }

  /**
   * Determines whether the users is already assigned a subscription.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity in question.
   *
   * @return bool
   *   Whether the user is assigned or not.
   */
  public function isUserAlreadyAssigned(UserInterface $user) {
    return $this->subscriptionLoader()->isUserAlreadyAssigned($this, $user);
  }

  /**
   * If we have one of more term then we can class this a renewal.
   *
   * @return bool
   *   If we have more than one term id
   *   then the subscription is in a renewal state.
   */
  public function isRenewal() {
    return count($this->getTermIds()) >= 1;
  }

  /**
   * Get a list of term ids.
   *
   * @return array|int
   *   Return an array of term ids an empty array
   *   will be returned is none found.
   */
  public function getTermIds() {
    return \Drupal::entityQuery('subscription_term')
      ->condition('subscription_entity_id', $this->id())
      ->sort('start_date')->execute();
  }

  /**
   * Get a future list of term ids by their state.
   *
   * @param int $state
   *   Constant state defined in module file.
   *
   * @return array
   *   Return an array of objects that contain term ids
   *   an empty array will be returned if none are found.
   */
  public function getFutureTermIdsByState($state) {
    $connection = Database::getConnection();
    $result = $connection->query('
      SELECT st.id
      FROM {subscription_term_field_data} fd
      LEFT JOIN {subscription_term} st ON st.id = fd.id AND st.type=fd.type
      LEFT JOIN {subscription_field_data} sd ON fd.subscription_entity_id = sd.id
      WHERE fd.term_status = :subscription_state
      AND (
        CASE sd.grace_period_unit
        WHEN \'Days\' THEN DATE_ADD(fd.end_date, INTERVAL sd.grace_period_value DAY)
        WHEN \'Month\' THEN DATE_ADD(fd.end_date, INTERVAL sd.grace_period_value MONTH)
        WHEN \'Year\' THEN DATE_ADD(fd.end_date, INTERVAL sd.grace_period_value YEAR)
        ELSE fd.end_date
        END > :now_datetime
        )
      AND fd.subscription_entity_id = :subscription', [
      ':subscription_state' => $state,
      ':now_datetime' => date('Y-m-d\TH:i:s'),
      ':subscription' => $this->id(),
    ])->fetchAll();
    return $result;
  }

  /**
   * Get a list of term ids by their state.
   *
   * @param int $state
   *   Constant state defined in module file.
   *
   * @return array
   *   Return an array of term ids
   *   an empty array will be returned if none are found.
   */
  public function getTermIdsByState($state) {
    return \Drupal::entityQuery('subscription_term')
      ->condition('subscription_entity_id', $this->id())
      ->condition('term_status', $state)
      ->sort('start_date')->execute();
  }

  /**
   * Get latest term.
   *
   * @return array
   *   An array of objects this will just be the one item however.
   */
  public function getLatestTerm() {
    $term_ids = $this->getTermIds();
    $latest_term = array();
    if (!empty($term_ids)) {
      $latest_term_id = array_pop($term_ids);
      $latest_term = $this->subscriptionLoader()->loadSubscriptionTermById($latest_term_id);
    }
    return $latest_term;
  }

  /**
   * Renew's a subscription.
   */
  public function renew() {
    $latestTerm = $this->getLatestTerm();

    $new_term = SubscriptionTerm::create(['type' => $latestTerm->getType()]);
    $new_term->setStartDate($latestTerm->getEndDate());
    $new_term->setOwnerId(\Drupal::currentUser()->id());
    $new_term->setPublished(1);
    $new_term->setSubscriptionEntityId($this->id());
    $new_term->setCreatedTime(time());
    $new_term->setRevisionCreationTime(time());
    $new_term->setRevisionUserId(\Drupal::currentUser()->id());
    $new_term->setRevisionTranslationAffected(TRUE);
    $new_term->save();
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
   * Extend the postSave method that creates a subscription reference.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage object.
   * @param bool $update
   *   Whether the entity is being updated or not.
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update === FALSE) {
      $entity = $this::load($this->id());
      $ref = str_pad($this->id(), 8, '0', STR_PAD_LEFT);
      $entity->set('subscription_ref', $ref);
      $entity->save();
    }

  }

  /**
   * Wrapper method for getting the subscription owner.
   *
   * @return \Drupal\user\UserInterface
   *   The user object.
   */
  public function getSubscriptionOwner() {
    return $this->get('subscription_owner_uid')->entity;
  }

  /**
   * Sets the subscription owner.
   *
   * If the subscription is active and a user has been added to the subscription
   * then trigger the necessary events.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return $this
   *   The subscription object using this context.
   */
  public function setSubscriptionOwner(UserInterface $user) {
    $this->set('subscription_owner_uid', ['target_id' => $user->id()]);

    // If we set the subscription owner and the subscription is active then
    // we need them to inherit all the benefits.
    if ($this->isActive()) {
      $this->triggerEventByState(SUBSCRIPTION_ACTIVE);
    }
    else {
      $this->triggerEventByState(SUBSCRIPTION_EXPIRED);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the subscription owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    // If this is a new subscription then set it to pending
    if ($this->isNew()) {
      $this->set('subscription_status', SUBSCRIPTION_PENDING);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionRef() {
    return $this->get('subscription_ref')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscriptionRef($ref) {
    $this->set('subscription_ref', $ref);
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->get('subscription_status')->value == SUBSCRIPTION_ACTIVE;
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
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * Get the subscription type entity.
   *
   * @return mixed
   *   A subscription type entity or null.
   */
  public function getSubscriptionTypeEntity() {
    return $this->get('type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function activateSubscription() {
    $this->set('subscription_status', SUBSCRIPTION_ACTIVE);
    $this->save();
    // Fire off an event.
    $this->triggerEventByState(SUBSCRIPTION_ACTIVE);
  }

  /**
   * Deactivates the subscription.
   */
  public function deActivateSubscription() {
    if (empty($this->getFutureTermIdsByState(SUBSCRIPTION_ACTIVE))) {
      $this->set('subscription_status', SUBSCRIPTION_EXPIRED);
      $this->save();
      $this->triggerEventByState(SUBSCRIPTION_EXPIRED);
    }
  }

  /**
   * Badly named function but basically this gets the remaining time left.
   *
   * @param int $numberOfMonthsToReduceOffTheEndDate
   *   Number which reduces the number of months
   *   from the end date of a subscription term.
   *
   * @return mixed
   *   Number of days or NULL if the current date is
   *   not in between the end date and the end date minus the number of months.
   */
  public function getTimeLeftByRemainingMonths($numberOfMonthsToReduceOffTheEndDate) {
    $days = NULL;
    $latestTerm = $this->getLatestTerm();
    $endDateString = $latestTerm->get('end_date')->value;
    $endDateByMonthNumber = new \DateTime($endDateString);
    $dateNow = new \DateTime();
    $endDate = new \DateTime($endDateString);
    $endDateByMonthNumber->modify('-' . $numberOfMonthsToReduceOffTheEndDate . ' month');

    if ($endDateByMonthNumber < $dateNow && $dateNow < $endDate) {
      $interval = $endDate->diff($dateNow);
      $days = $interval->days;
    }

    return $days;
  }

  /**
   * Cancel the subscription and all active/pending terms.
   */
  public function cancelSubscription() {
    $this->set('subscription_status', SUBSCRIPTION_CANCELLED);
    $this->save();
    $term_ids = array_merge(
      $this->getTermIdsByState(SUBSCRIPTION_PENDING),
      $this->getTermIdsByState(SUBSCRIPTION_ACTIVE)
    );
    if ($term_ids) {
      foreach ($term_ids as $term_id) {
        /** @var SubscriptionTerm $term */
        $term = $this->subscriptionLoader()->loadSubscriptionTermById($term_id);
        $term
          ->cancelTerm()
          ->save();
      }
    }
    $this->triggerEventByState(SUBSCRIPTION_CANCELLED);
  }

  /**
   * Cancel the subscription.
   */
  public function pendingSubscription() {
    $this->set('subscription_status', SUBSCRIPTION_PENDING);
    $this->save();
    $this->triggerEventByState(SUBSCRIPTION_CANCELLED);
  }

  /**
   * Trigger events by a given state.
   *
   * @param int $state
   *   The State at which the event is in see subscription.module file
   *   for constants.
   */
  public function triggerEventByState($state) {
    $dispatcher = \Drupal::service('event_dispatcher');
    $stateEvent = new SubscriptionStateUpdatedEvent($this, $state);
    $dispatcher->dispatch('subscription.state_updated', $stateEvent);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $roles = user_roles(TRUE);
    foreach ($roles as $role_id => $role) {
      $siteRoles[$role_id] = $role->label();
    }

    $fields['subscription_owner_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID of the Subscription entity. They are the owner'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['term_length'] = BaseFieldDefinition::create('string')
      ->setLabel(t('The length of terms on the subscription'))
      ->setDescription(t('How long each term should last for'))
      ->setDefaultValue('1 year')
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values', array(
        '1 year' => '1 year',
        '6 month' => '6 months',
        '1 month' => '1 month',
      ))
      ->setSetting('allowed_values_function', '')
      ->setDisplayOptions('form', array(
        'weight' => 1,
        'type' => 'options_select',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setRevisionable(TRUE);

    $fields['grace_period_value'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Grace period value'))
      ->setDescription(t('The grace value period used to extend a subscription before it expires.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSettings(array(
        'min' => 1,
      ))
      ->setDisplayOptions('form', array(
        'weight' => 2,
        'type' => 'number',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_unformatted',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['grace_period_unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Grace period unit'))
      ->setDescription(t('The unit grace period used to extend a subscription before it expires.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSetting('allowed_values', array(
        'Year' => 'Years',
        'Month' => 'Months',
        'Day' => 'Days',
      ))
      ->setSetting('allowed_values_function', '')
      ->setDisplayOptions('form', array(
        'weight' => 3,
        'type' => 'options_select',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['subscription_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscription ref'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDescription(t('The human readable subscription ref of the Subscription entity.'));

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Subscription entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Subscription is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['subscription_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('The subscription status'))
      ->setDescription(t('Whether or not the subscription is active, cancelled, expired or pending. This is dependant on term data'))
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
