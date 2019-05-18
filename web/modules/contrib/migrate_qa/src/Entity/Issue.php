<?php
namespace Drupal\migrate_qa\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Migrate QA Issue.
 *
 * @ContentEntityType(
 *   id = "migrate_qa_issue",
 *   label = @Translation("Migrate QA Issue"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\migrate_qa\Controller\IssueListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\migrate_qa\Form\IssueForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\migrate_qa\IssueAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "migrate_qa_issue",
 *   revision_table = "migrate_qa_issue_revision",
 *   admin_permission = "administer migrate_qa_issue entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "summary",
 *     "uuid" = "uuid",
 *     "revision" = "vid",
 *   },
 *   links = {
 *     "canonical" = "/migrate-qa-issue/{migrate_qa_issue}",
 *     "add-form" = "/migrate-qa-issue/add",
 *     "edit-form" = "/migrate-qa-issue/{migrate_qa_issue}/edit",
 *     "delete-form" = "/migrate-qa-issue/{migrate_qa_issue}/delete",
 *     "collection" = "/admin/structure/migrate-qa/issue",
 *   },
 *   fieldable = TRUE,
 *   field_ui_base_route = "migrate_qa_issue.settings",
 * )
 */
class Issue extends ContentEntityBase implements IssueInterface {

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
   * Issue constructor.
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
      ->setDescription(t('The time that the status was last edited.'))
      ->setRevisionable(TRUE);

    // Add the Original ID field.
    $fields['summary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Issue Summary'))
      ->setDescription(t('Short summary of the issue'))
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

    // Add the Notes field.
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Details about the issue'))
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

    // Add Status field.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSettings([
        'allowed_values' => [
          'open' => 'Open',
          'resolved' => 'Resolved',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'label' => 'inline',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add the Field field, yes, the Field field.
    $fields['field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field'))
      ->setDescription(t('Field where the issue is present, e.g. body field, date field, etc.'))
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
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    return $fields;
  }
}
