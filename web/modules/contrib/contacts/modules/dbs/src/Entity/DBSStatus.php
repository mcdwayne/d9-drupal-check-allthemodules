<?php

namespace Drupal\contacts_dbs\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Defines the dbs status entity class.
 *
 * @ContentEntityType(
 *   id = "dbs_status",
 *   label = @Translation("DBS Status"),
 *   label_singular = @Translation("DBS status"),
 *   label_plural = @Translation("DBS statuses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count DBS status",
 *     plural = "@count DBS statuses"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\contacts_dbs\DBSStatusStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\contacts_dbs\Form\DBSStatusForm",
 *       "add" = "Drupal\contacts_dbs\Form\DBSStatusForm",
 *       "edit" = "Drupal\contacts_dbs\Form\DBSStatusForm",
 *       "archive" = "Drupal\contacts_dbs\Form\DBSStatusArchiveForm",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\contacts_dbs\DBSStatusViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\contacts_dbs\DBSStatusHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "dbs_status",
 *   data_table = "dbs_status_field_data",
 *   revision_table = "dbs_status_revision",
 *   revision_data_table = "dbs_status_field_revision",
 *   translatable = TRUE,
 *   show_revision_ui = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   admin_permission = "manage dbs statuses",
 *   links = {
 *     "add-form" = "/admin/dbs-status/add/{user}/{dbs_workforce}",
 *     "edit-form" = "/admin/dbs-status/{dbs_status}/edit",
 *     "archive-form" = "/admin/dbs-status/{dbs_status}/archive",
 *   }
 * )
 */
class DBSStatus extends RevisionableContentEntityBase implements DBSStatusInterface {

  use StringTranslationTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('DBS status for user @uid for workforce @workforce', [
      '@uid' => $this->getOwner()->getAccountName(),
      '@workforce' => $this->get('workforce')->entity->label(),
    ]);
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
    return $this->set('created', $timestamp);
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
    return $this->set('uid', $account->id());
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
    return $this->set('uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function archive() {
    $this->set('archived', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Set/clear expiry when status changes to/from cleared.
   */
  public function preSave(EntityStorageInterface $storage) {
    /* @var \Drupal\contacts_dbs\DBSStatusStorageInterface $storage */
    if ($this->isNew()) {
      /* @var \Drupal\contacts_dbs\DBSManager $dbs_manager */
      $dbs_manager = \Drupal::service('contacts_dbs.dbs_manager');
      // Check that we aren't creating duplicates.
      if ($dbs_manager->getDbs($this->getOwnerId(), $this->get('workforce')->target_id, FALSE)) {
        throw new \Exception('Cannot create multiple DBS status items for the same workforce.');
      }
    }

    $cleared_statuses = $this::getClearedStatuses();
    if (in_array($this->get('status')->value, $cleared_statuses)) {
      // Find the date at which this status cleared DBS.
      $valid_from = $this->get('valid_from')->date;
      if (!$valid_from) {
        // We need to find a suitable valid from time. If we are changing to
        // cleared, use now.
        $original = $this->original ? $this->original : NULL;
        if (!$original || !in_array($original->get('status')->value, $cleared_statuses)) {
          $valid_from = new DrupalDateTime();
        }
        // Otherwise we pull the latest time from the revisions.
        else {
          $revision = $this->getStatusRevision($this->get('status')->value, TRUE);
          if ($revision) {
            /* @var \Drupal\contacts_dbs\Entity\DBSStatusInterface $revision */
            $valid_from = new DrupalDateTime();
            $valid_from->setTimestamp($revision->getRevisionCreationTime());
          }
        }

        if ($valid_from) {
          $this->set('valid_from', $valid_from->format($this::DATE_FORMAT));
        }
      }

      // Set the expiry from the valid from date unless overridden.
      $expiry = $this->get('expiry_override')->date;
      if (!$expiry) {
        if ($valid_from) {
          $workforce_id = $this->get('workforce')->target_id;
          /* @var \Drupal\contacts_dbs\Entity\DBSWorkforceInterface $workforce */
          $workforce = $this->entityTypeManager()->getStorage('dbs_workforce')->load($workforce_id);
          $validity = $workforce->getValidity();
          $expiry = clone $valid_from;
          $expiry->add(new \DateInterval("P{$validity}Y"));
        }
      }
      if ($expiry) {
        $this->set('expiry', $expiry->format($this::DATE_FORMAT));
      }
    }
    else {
      // Clear expiry.
      $this->set('expiry', NULL);
      $this->set('expiry_override', NULL);
      $this->set('valid_from', NULL);
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
    $is_new_revision = $this->isNewRevision();
    if ($is_new_revision) {
      $record->revision_created = self::getRequestTime();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The user ID of the author.'))
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
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
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['workforce'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('DBS Workforce'))
      ->setSetting('target_type', 'dbs_workforce')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The status of the dbs status item.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue('letter_required')
      ->setSetting('allowed_values_function', [static::class, 'getStatuses'])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'select',
      ])
      ->setDisplayOptions('form', [
        'type' => 'select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['archived'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Archived'))
      ->setDescription(new TranslatableMarkup('Whether the dbs status item is active.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE);

    $fields['valid_from'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Valid from'))
      ->setDescription(new TranslatableMarkup('The time the dbs status item was cleared.'))
      ->setSetting('datetime_type', 'date')
      ->setRevisionable(TRUE);

    $fields['expiry'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Expiry'))
      ->setDescription(new TranslatableMarkup('The time the dbs status item expires.'))
      ->setSetting('datetime_type', 'date')
      ->setRevisionable(TRUE);

    $fields['expiry_override'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Expiry override'))
      ->setDescription(new TranslatableMarkup('Set an override on the expiry. If blank, the system wide DBS length will be used.'))
      ->setRevisionable(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ]);

    $fields['certificate_no'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Certificate number'))
      ->setDescription('')
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Notes'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'basic_string',
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Authored on'))
      ->setDescription(new TranslatableMarkup('The time the dbs status item was created.'))
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getRequestTime')
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time the dbs status item was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * Get the available consent modes.
   *
   * @return array
   *   Array of consent modes.
   */
  public static function getStatuses() {
    // @todo Make alterable.
    return [
      'letter_required' => new TranslatableMarkup('Letter required'),
      'letter_sent' => new TranslatableMarkup('Letter sent'),
      'disclosure_requested' => new TranslatableMarkup('Disclosure requested'),
      'form_incorrect' => new TranslatableMarkup('Form incorrect - return to sender'),
      'living_abroad' => new TranslatableMarkup('Living abroad'),
      'dbs_clear' => new TranslatableMarkup('DBS clear'),
      'dbs_not_clear' => new TranslatableMarkup('DBS not clear'),
      'dbs_expired' => new TranslatableMarkup('DBS expired'),
      'dbs_exception' => new TranslatableMarkup('DBS exception'),
      'update_service_check_required' => new TranslatableMarkup('Update service check required'),
      'update_service_checked' => new TranslatableMarkup('Update service checked'),
      'portability_check' => new TranslatableMarkup('Portability check'),
      'disclosure_accepted' => new TranslatableMarkup('Disclosure Accepted'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isValid($valid_at = NULL) {
    // Check the valid date if necessary.
    if ($valid_at) {
      if ($valid_at <= $this->get('expiry')->value) {
        return TRUE;
      }
    }
    // @todo Should we check this or assume any process calling this method
    // has already checked status for 'archived'?
    else {
      $current = new DrupalDateTime();
      if ($current->format($this::DATE_FORMAT) <= $this->get('expiry')->value) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Wrapper method for request timestamp.
   *
   * @return int
   *   The request timestamp.
   */
  public static function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public static function getClearedStatuses() {
    return [
      'dbs_clear',
      'disclosure_accepted',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusRevision($status, $skip_current = FALSE) {
    $storage = \Drupal::entityTypeManager()->getStorage('dbs_status');

    $query = $storage->getQuery()
      ->allRevisions()
      ->condition('status', $status)
      ->sort($this->getEntityType()->getKey('revision'), 'DESC');

    // Skip the current revision if already been saved to database.
    if ($skip_current) {
      $query->range(1, 1);
    }
    else {
      $query->range(0, 1);
    }

    $result = $query->execute();

    if (!empty($result)) {
      $id = array_keys($result)[0];
      return $storage->loadRevision($id);
    }

    return FALSE;
  }

}
