<?php


namespace Drupal\x_reference\Entity;


use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\x_reference\XReferenceHandlerInterface;

/**
 * @property FieldItemListInterface entity_source
 * @property FieldItemListInterface entity_type
 * @property FieldItemListInterface entity_id
 *
 * @ContentEntityType(
 *   id = "x_referenced_entity",
 *   label = @Translation("X-referenced entity"),
 *   base_table = "x_referenced_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "entity_id",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\x_reference\Entity\Controller\XReferencedEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer x_referenced_entity",
 *   links = {
 *     "canonical" = "/x_referenced_entity/{x_referenced_entity}",
 *     "add-form" = "/x_referenced_entity/add",
 *     "edit-form" = "/x_referenced_entity/{x_referenced_entity}/edit",
 *     "delete-form" = "/x_referenced_entity/{x_referenced_entity}/delete",
 *     "collection" = "/x_referenced_entity/list"
 *   },
 * )
 */
class XReferencedEntity extends ContentEntityBase {

  const ENTITY_TYPE = 'x_referenced_entity';

  /**
   * {@inheritdoc}
   */
  public function label() {
    return implode(':', [
      $this->entity_source->value,
      $this->entity_type->value,
      $this->entity_id->value,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity id'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * @param string $entity_source
   * @param string $entity_type
   * @param string $entity_id
   * @param bool $saveCreated
   *
   * @return XReferencedEntity
   */
  public function createOrLoad($entity_source, $entity_type, $entity_id, $saveCreated = TRUE) {
    /** @var XReferenceHandlerInterface $handler */
    $handler = \Drupal::service('x_reference_handler');
    return $handler->createOrLoadXReferencedEntity($entity_source, $entity_type, $entity_id, $saveCreated);
  }

}
