<?php

namespace Drupal\mailing_list\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mailing_list\SubscriptionInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;

/**
 * Defines the subscription entity class.
 *
 * @ContentEntityType(
 *   id = "mailing_list_subscription",
 *   label = @Translation("Mailing list subscription"),
 *   label_singular = @Translation("Mailing list subscription"),
 *   label_plural = @Translation("Mailing list subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count subscription",
 *     plural = "@count subscriptions",
 *   ),
 *   bundle_label = @Translation("Mailing list"),
 *   handlers = {
 *     "list_builder" = "Drupal\mailing_list\SubscriptionListBuilder",
 *     "view_builder" = "Drupal\mailing_list\SubscriptionViewBuilder",
 *     "access" = "Drupal\mailing_list\SubscriptionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\mailing_list\Form\SubscriptionForm",
 *       "add" = "Drupal\mailing_list\Form\SubscriptionForm",
 *       "edit" = "Drupal\mailing_list\Form\SubscriptionForm",
 *       "delete" = "Drupal\mailing_list\Form\SubscriptionCancelForm",
 *       "block" = "Drupal\mailing_list\Form\SubscriptionForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *   },
 *   base_table = "mailing_list_subscription",
 *   data_table = "mailing_list_subscription_field_data",
 *   translatable = TRUE,
 *   list_cache_contexts = { "session" },
 *   entity_keys = {
 *     "id" = "sid",
 *     "uuid" = "uuid",
 *     "bundle" = "mailing_list",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uid" = "uid",
 *     "email" = "email",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "mailing_list",
 *   field_ui_base_route = "entity.mailing_list.edit_form",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/mailing-list/subscription/{mailing_list_subscription}",
 *     "add-form" = "/mailing-list/subscribe/{mailing_list}",
 *     "edit-form" = "/mailing-list/subscription/{mailing_list_subscription}/edit",
 *     "delete-form" = "/mailing-list/subscription/{mailing_list_subscription}/cancel",
 *     "collection" = "/admin/people/mailing-list-subscription",
 *     "manage" = "/mailing-list/subscription",
 *   },
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getList() {
    return $this->entityTypeManager()->getStorage('mailing_list')->load($this->getListId());
  }

  /**
   * {@inheritdoc}
   */
  public function getListId() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
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
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status ? SubscriptionInterface::ACTIVE : SubscriptionInterface::INACTIVE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail($obfuscate = FALSE) {
    $email = $this->get('email')->value;
    if ($obfuscate) {
      $parts = explode('@', $email);
      // User.
      $u = $parts[0];
      // Domain.
      $d = $parts[1];
      // Domain first point.
      $dp = strpos($d, '.');
      $email = $u[0] . str_repeat('*', strlen($u) - 1)
        . '@'
        . $d[0] . str_repeat('*', $dp - 1) . substr($d, $dp);
    }
    return $email;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->set('email', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessHash() {
    $data = $this->uuid()
    . $this->getChangedTime()
    . $this->getOwnerId();
    return Crypt::hmacBase64($data, \Drupal::service('private_key')->get());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['session']);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // Calculate an after-form destination URL.
    if ($rel == 'form-destination') {
      /** @var \Drupal\Core\Url $url */
      $url = NULL;
      if ($dst = $this->getList()->getFormDestination()) {
        $url = parent::toUrl($dst, $options);
      }

      // Default destination behaviour: canonical, user susbscriptions or front.
      if (!$url || !$url->access()) {
        if ($this->access('view')) {
          $url = parent::toUrl('canonical', $options);
        }
        else {
          $url = parent::toUrl('manage', $options);
          if (!$url->access()) {
            $url = Url::fromRoute('<front>');
          }
        }
      }

      return $url;
    }

    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Generic base fields.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Subscription ID custom label & description.
    $fields['sid']->setLabel(t('Subscription ID'))
      ->setDescription(t('The ID of the subscription.'));

    // Bundle field custom description.
    $fields['mailing_list']->setDescription(t('The mailing list to which this subscription belongs.'));

    // User ID (subscription author/owner).
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setRequired(TRUE)
      ->setDescription(t('The user ID of the subscriber.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\mailing_list\Entity\Subscription::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
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
      ->setDisplayConfigurable('form', TRUE);

    // Subscription title or name.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Subscription email address.
    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address of this subscription.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Subscription status (active/inactive).
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Indicates whether the subscription is active.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(SubscriptionInterface::ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'title' => t('Subscription status'),
        'label' => 'above',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Subscription creation date.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Added on'))
      ->setDescription(t('The time that the subscription was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Subscription last changed time.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the subscription was last updated.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Applies subscription limits on subscription creation or activation.
    if ($this->isNew() && $this->isActive() ||
      $this->isActive() && isset($this->original) && !$this->original->isActive()) {
      $list = $this->getList();
      $max_reached = FALSE;

      if ($max_per_email = intval($list->getLimitByEmail())) {
        // Count existent subscriptions with the same email.
        $query = \Drupal::entityQuery('mailing_list_subscription')
          ->condition('status', SubscriptionInterface::ACTIVE)
          ->condition('email', $this->getEmail())
          ->count();

        // Exclude itself.
        if ($this->id()) {
          $query->condition('sid', $this->id(), '<>');
        }

        $max_reached = $query->execute() >= $max_per_email;
      }

      if (!$max_reached && $max_per_user = intval($list->getLimitByUser())) {
        // Count existent subscriptions with the same email.
        $query = \Drupal::entityQuery('mailing_list_subscription')
          ->condition('status', SubscriptionInterface::ACTIVE)
          ->condition('uid', \Drupal::currentUser()->id())
          ->count();

        // Exclude itself.
        if ($this->id()) {
          $query->condition('sid', $this->id(), '<>');
        }

        $max_reached = $query->execute() >= $max_per_user;
      }

      // Limit reached.
      if ($max_reached) {
        // Set this as inactive.
        $this->setStatus(SubscriptionInterface::INACTIVE);

        // Send notification email to subscriber.
        \Drupal::service('plugin.manager.mail')->mail(
          'mailing_list',
          'subscription_limit_reached',
          $this->getEmail(),
          $this->language(),
          [
            'subscription' => $this,
          ]
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    if (!isset($values['uid'])) {
      $values['uid'] = \Drupal::currentUser()->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Grant session access to anonymous.
    if ($this->getOwner()->isAnonymous() && \Drupal::currentUser()->isAnonymous()) {
      \Drupal::service('mailing_list.manager')->grantSessionAccess($this);
    }
  }

}
