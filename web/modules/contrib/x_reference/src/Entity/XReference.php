<?php


namespace Drupal\x_reference\Entity;


use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\x_reference\Entity\XReferencedEntity;

/**
 * @property FieldItemListInterface source_entity
 * @property FieldItemListInterface target_entity
 *
 * @ContentEntityType(
 *   id = "x_reference",
 *   label = @Translation("X-Reference"),
 *   base_table = "x_reference",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\x_reference\Entity\Controller\XReferenceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   bundle_entity_type = "x_reference_type",
 *   admin_permission = "administer x_reference",
 *   links = {
 *     "canonical" = "/x_reference/{x_reference}",
 *     "add-form" = "/x_reference/add",
 *     "edit-form" = "/x_reference/{x_reference}/edit",
 *     "delete-form" = "/x_reference/{x_reference}/delete",
 *     "collection" = "/x_reference/list"
 *   },
 * )
 */
class XReference extends ContentEntityBase {

  const ENTITY_TYPE = 'x_reference';

  /**
   * {@inheritdoc}
   */
  public function label() {
    $sourceEntity = $this->getSourceEntity();
    $targetEntity = $this->getTargetEntity();
    return t('@type: @source->@target', [
      '@type' => $this->bundle(),
      '@source' => $sourceEntity ? $sourceEntity->label() : 'NULL',
      '@target' => $targetEntity ? $targetEntity->label() : 'NULL',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['source_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source entity id'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'x_referenced_entity')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('XReferencedEntityConstraint');

    $fields['target_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source entity id'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'x_referenced_entity')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('XReferencedEntityConstraint');

    return $fields;
  }

  /**
   * @return XReferencedEntity|NULL
   */
  public function getSourceEntity() {
    return $this->source_entity->entity;
  }

  /**
   * @return XReferencedEntity|NULL
   */
  public function getTargetEntity() {
    return $this->target_entity->entity;
  }

}
