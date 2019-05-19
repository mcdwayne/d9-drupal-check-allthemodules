<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Subscription Term entity.
 *
 * @ingroup subscription
 *
 * @ContentEntityType(
 *   id = "subscription_term",
 *   label = @Translation("Subscription Term"),
 *   bundle_label = @Translation("Subscription Term type"),
 *   handlers = {
 *     "storage" = "Drupal\subscription_entity\SubscriptionTermStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\subscription_entity\SubscriptionTermListBuilder",
 *     "views_data" = "Drupal\subscription_entity\Entity\SubscriptionTermViewsData",
 *     "translation" = "Drupal\subscription_entity\SubscriptionTermTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\subscription_entity\Form\SubscriptionTermForm",
 *       "add" = "Drupal\subscription_entity\Form\SubscriptionTermForm",
 *       "edit" = "Drupal\subscription_entity\Form\SubscriptionTermForm",
 *       "delete" = "Drupal\subscription_entity\Form\SubscriptionTermDeleteForm",
 *     },
 *     "access" = "Drupal\subscription_entity\SubscriptionTermAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\subscription_entity\SubscriptionTermHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "subscription_term",
 *   data_table = "subscription_term_field_data",
 *   revision_table = "subscription_term_revision",
 *   revision_data_table = "subscription_term_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer subscription term entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/subscription-term/subscription_term/{subscription_term}",
 *     "add-page" = "/subscription-term/subscription_term/add",
 *     "add-form" = "/subscription-term/subscription_term/add/{subscription_term_type}",
 *     "edit-form" = "/subscription-term/subscription_term/{subscription_term}/edit",
 *     "delete-form" = "/subscription-term/subscription_term/{subscription_term}/delete",
 *     "version-history" = "/subscription-term/subscription_term/{subscription_term}/revisions",
 *     "revision" = "/subscription-term/subscription_term/{subscription_term}/revisions/{subscription_term_revision}/view",
 *     "revision_revert" = "/subscription-term/subscription_term/{subscription_term}/revisions/{subscription_term_revision}/revert",
 *     "translation_revert" = "/subscription-term/subscription_term/{subscription_term}/revisions/{subscription_term_revision}/revert/{langcode}",
 *     "revision_delete" = "/subscription-term/subscription_term/{subscription_term}/revisions/{subscription_term_revision}/delete",
 *     "collection" = "/subscription-term/subscription_term",
 *   },
 *   bundle_entity_type = "subscription_term_type",
 *   field_ui_base_route = "entity.subscription_term_type.edit_form"
 * )
 */
class SubscriptionTerm extends RevisionableContentEntityBase implements SubscriptionTermInterface {

  use EntityChangedTrait;

  protected $subscriptionType;

  /**
   * Gets the subscription loader.
   *
   * @return \Drupal\subscription_entity\SubscriptionLoaderInterface
   *   Subscription loader service.
   */
  protected function subscriptionLoader() {
    return \Drupal::service('subscription_entity.subscription_entity_loader');
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
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
    // make the subscription_term owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
    // Sets the end date.
    // If the term we have created is an active term then
    // we should reflect whatever the status has been set against
    // the term to the subscription.
    $subscription = $this->loadSubscriptionByTerm();
    $length = !empty($subscription->get('term_length')->value) ? $subscription->get('term_length')->value : '1 year';

    // Only set an end date if it was not defined upon creation.
    if (!$this->getEndDate()) {
      $date = new \DateTime($this->get('start_date')->value);
      $date->modify('+' . $length);
      $this->setEndDate($date->format('Y-m-d\TH:i:s'));
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
   * Sets the $subscriptionEntityId property.
   *
   * @param int $subscriptionEntityId
   *   The unique identifier.
   *
   * @return $this
   */
  public function setSubscriptionEntityId($subscriptionEntityId) {
    $this->subscription_entity_id->setValue(['target_id' => $subscriptionEntityId]);
    return $this;
  }

  /**
   * Load the corresponding subscription associated to the term.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function loadSubscriptionByTerm() {
    return $this->entityTypeManager()->getStorage('subscription')->load($this->getSubscriptionEntityId());
  }

  /**
   * Gets the related subscription entity id.
   */
  public function getSubscriptionEntityId() {
    return $this->subscription_entity_id->getValue()[0]['target_id'];
  }

  /**
   * Set the start date for a term.
   *
   * @param string $startDate
   *   A date string.
   */
  public function setStartDate($startDate) {
    $this->set('start_date', $startDate);
  }

  /**
   * Gets the start date for a term.
   *
   * @return mixed
   *   The start date as a string.
   */
  public function getStartDate() {
    return $this->get('start_date')->value;
  }

  /**
   * Set the end date for a term.
   *
   * @param string $endDate
   *   A date string.
   */
  public function setEndDate($endDate) {
    $this->set('end_date', $endDate);
  }

  /**
   * Gets the end date for a term.
   *
   * @return mixed
   *   The end date as a string.
   */
  public function getEndDate() {
    return $this->get('end_date')->value;
  }

  /**
   * Method checks to see if we have an active term.
   *
   * @return bool
   *   Whether or not the term is active.
   */
  public function isActiveTerm() {
    $today = new \DateTime();
    $today->format('Y-m-d\TH:i:s');
    $is_between = FALSE;
    $today = get_object_vars($today)['date'];

    if ($today >= $this->getStartDate() && $today <= $this->getEndDate()) {
      $is_between = TRUE;
    }
    return $is_between;
  }

  /**
   * Activates the term.
   */
  public function activateTerm() {
    $this->term_status->setValue(SUBSCRIPTION_ACTIVE);
    $subscription = $this->loadSubscriptionByTerm();
    $subscription->activateSubscription();
    $subscription->save();
  }

  /**
   * Deactivates the term.
   */
  public function deActivateTerm() {
    $this->term_status->setValue(SUBSCRIPTION_EXPIRED);
    /** @var Subscription $subscription */
    $subscription = $this->loadSubscriptionByTerm();
    $subscription->deActivateSubscription();
    $subscription->save();
  }

  /**
   * Cancels the term.
   */
  public function cancelTerm() {
    $this->term_status->setValue(SUBSCRIPTION_CANCELLED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['subscription_entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subscription id'))
      ->setDescription(t('The ID of subscription entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'subscription')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['term_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('The terms status'))
      ->setDescription(t('Whether or not the term is active, cancelled, expired or pending.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue(SUBSCRIPTION_PENDING)
      ->setSettings(array(
        'min' => 0,
        'max' => 3,
        'allowed_values' => array(
          SUBSCRIPTION_ACTIVE => t('Active'),
          SUBSCRIPTION_PENDING => t('Pending'),
          SUBSCRIPTION_EXPIRED => t('Expired'),
          SUBSCRIPTION_CANCELLED => t('Cancelled'),
        ),
        'allowed_values_function' => '',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['timezone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Timezone'))
      ->setDescription(t('The timezone of the term.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDefaultValue('UTC')
      ->setSetting('allowed_values', array(
        'UTC' => 'UTC',
      ))
      ->setSetting('allowed_values_function', '')
      ->setDisplayOptions('form', array(
        'weight' => 3,
        'type' => 'options_select',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date'))
      ->setDescription(t('The date the subscription term has started.'))
      ->setSetting('datetime_type', 'datetime')
      ->setSetting('default_date_type', 'now')
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'weight' => 4,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date'))
      ->setDescription(t('The date the subscription term has ended.'))
      ->setSetting('datetime_type', 'datetime')
      ->setSetting('default_date_type', 'now')
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Subscription Term entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 7,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Subscription Term is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

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
