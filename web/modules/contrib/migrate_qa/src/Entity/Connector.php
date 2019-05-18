<?php
namespace Drupal\migrate_qa\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Migrate QA Connector.
 *
 * @ContentEntityType(
 *   id = "migrate_qa_connector",
 *   label = @Translation("Migrate QA Connector"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\migrate_qa\Controller\ConnectorListBuilder",
 *     "views_data" = "Drupal\migrate_qa\Entity\ConnectorViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\migrate_qa\ConnectorAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "migrate_qa_connector",
 *   revision_table = "migrate_qa_connector_revision",
 *   admin_permission = "administer migrate_qa_connector entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/migrate-qa-connector/{migrate_qa_connector}",
 *     "add-form" = "/migrate-qa-connector/add",
 *     "edit-form" = "/migrate-qa-connector/{migrate_qa_connector}/edit",
 *     "delete-form" = "/migrate-qa-connector/{migrate_qa_connector}/delete",
 *     "collection" = "/admin/structure/migrate-qa/connector",
 *   },
 *   fieldable = TRUE,
 *   field_ui_base_route = "migrate_qa_connector.settings",
 * )
 */
class Connector extends ContentEntityBase implements ConnectorInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    /** @var \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList $content */
    $content = $this->get('content');
    /** @var \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem $item */
    $item = $content->first();
    $entity = $item->entity;
    if (!empty($entity)) {
      return new TranslatableMarkup('@label (@type)', [
        '@label' => $entity->label(),
        '@type' => $item->target_type,
      ]);
    }
    return parent::label();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Add fields defined by the parent.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add Tracker field.
    $fields['tracker'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tracker'))
      ->setDescription('The tracker that relates to this migrated entity')
      ->setCardinality(1)
      ->setSettings([
        'target_type' => 'migrate_qa_tracker',
      ])
      ->setSetting('handler', 'default:migrate_qa_tracker')
      ->setSetting('handler_settings', [
        'target_bundles' => NULL,
        'auto_create' => FALSE,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -9,
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_entity_view',
        'weight' => -9,
        'label' => 'above',
        'settings' => [
          'link' => TRUE,
          'view_mode' => 'default',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Add Content field.
    $fields['content'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Content'))
      ->setDescription('Content that is migrated into the current site')
      ->setCardinality(1)
      ->setSettings([
        'exclude_entity_types' => TRUE,
        'entity_type_ids' => [
          'migrate_qa_connector' => 'migrate_qa_connector',
          'migrate_qa_flag' => 'migrate_qa_flag',
          'migrate_qa_issue' => 'migrate_qa_issue',
          'migrate_qa_tracker' => 'migrate_qa_tracker',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_default',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'type' => 'dynamic_entity_reference_label',
        'weight' => -10,
        'label' => 'inline',
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}
