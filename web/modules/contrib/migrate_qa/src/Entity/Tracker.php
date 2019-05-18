<?php

namespace Drupal\migrate_qa\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;
use Drupal\migrate_qa\Plugin\Field\FieldType\TrackerFlags;

/**
 * Migrate QA Tracker.
 *
 * @ContentEntityType(
 *   id = "migrate_qa_tracker",
 *   label = @Translation("Migrate QA Tracker"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\migrate_qa\Controller\TrackerListBuilder",
 *     "views_data" = "Drupal\migrate_qa\Entity\TrackerViewsData",
 *     "form" = {
 *       "default" = "Drupal\migrate_qa\Form\TrackerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\migrate_qa\TrackerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "migrate_qa_tracker",
 *   revision_table = "migrate_qa_tracker_revision",
 *   admin_permission = "administer migrate_qa_tracker entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "original_id",
 *     "uuid" = "uuid",
 *     "revision" = "vid",
 *   },
 *   links = {
 *     "canonical" = "/migrate-qa-tracker/{migrate_qa_tracker}",
 *     "add-form" = "/migrate-qa-tracker/add",
 *     "edit-form" = "/migrate-qa-tracker/{migrate_qa_tracker}/edit",
 *     "delete-form" = "/migrate-qa-tracker/{migrate_qa_tracker}/delete",
 *     "collection" = "/admin/structure/migrate-qa/tracker",
 *   },
 *   fieldable = TRUE,
 *   field_ui_base_route = "migrate_qa_tracker.settings",
 * )
 */
class Tracker extends ContentEntityBase implements TrackerInterface {

  use EntityChangedTrait;
  use RevisionLogEntityTrait;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Tracker constructor.
   *
   * Override default constructor to set some custom properties.
   *
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, bool $bundle = FALSE, array $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->time = \Drupal::service('datetime.time');
    $this->currentUser = \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->setRevisionUserId($this->currentUser->id());
    $timestamp = $this->time->getRequestTime();
    $this->setRevisionCreationTime($timestamp);
    $this->setNewRevision();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Add fields defined by the parent.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the revision metadata fields.
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    // Add the Changed field.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the tracker was last edited.'))
      ->setRevisionable(TRUE);

    // Add the Original ID field.
    $fields['original_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Original ID'))
      ->setDescription(t('The original ID of the entity being migrated.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Original URL field.
    $fields['original_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Original URL'))
      ->setDescription(t('The original URL of the entity being migrated'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_EXTERNAL,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'type' => 'link',
        'label' => 'inline',
        'weight' => -9,
        'settings' => [
          'trim_length' => 200,
          'target' => '_blank',
          'url_only' => FALSE,
          'url_plain' => FALSE,
          'rel' => '0',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -9,
        'settings' => [
          'placeholder_url' => 'URL to original content',
          'placeholder_title' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Add the Notes field.
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Add Is Key Content field.
    $fields['is_key_content'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is Key Content'))
      ->setDescription('Check if this item is top priority for review.')
      ->setSettings([
        'on_label' => t('Yes'),
        'off_label' => t('No'),
      ])
      ->setSetting('display_label', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -6,
      ])
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'label' => 'inline',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add Issues field.
    $fields['issues'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Issues'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'target_type' => 'migrate_qa_issue',
      ])
      ->setSetting('handler', 'default:migrate_qa_issue')
      ->setSetting('handler_settings', [
        'target_bundles' => NULL,
        'auto_create' => FALSE,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_entity_view',
        'label' => 'above',
        'weight' => -2,
        'settings' => [
          'link' => TRUE,
          'view_mode' => 'default',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add Flags computed field.
    $fields['flags'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Flags'))
      ->setComputed(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSettings([
        'target_type' => 'migrate_qa_flag',
      ])
      ->setSetting('handler', 'default:migrate_qa_flag')
      ->setClass(TrackerFlags::class)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
