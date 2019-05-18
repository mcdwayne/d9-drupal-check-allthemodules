<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the rental instance entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_rental_instance",
 *   label = @Translation("Rental instance"),
 *   label_collection = @Translation("Rental instances"),
 *   label_singular = @Translation("rental instance"),
 *   label_plural = @Translation("rental instances"),
 *   label_count = @PluralTranslation(
 *     singular = "@count rental instance",
 *     plural = "@count rental instances",
 *   ),
 *   bundle_label = @Translation("Rental instance type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_rental_reservation\RentalInstanceStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "inline_form" = "Drupal\commerce_rental_reservation\Form\RentalInstanceInlineForm",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_rental",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   base_table = "commerce_rental_instance",
 *   data_table = "commerce_rental_instance_field_data",
 *   entity_keys = {
 *     "id" = "instance_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   bundle_entity_type = "commerce_rental_instance_type",
 *   field_ui_base_route = "entity.commerce_rental_instance_type.edit_form",
 * )
 */
class RentalInstance extends ContentEntityBase implements RentalInstanceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['serial'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Serial Number'))
      ->setDescription(t('The serial number of the rental instance.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the rental instance.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The rental instance state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_rental_reservation\Entity\RentalInstance', 'getWorkflowId']);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the instance was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // This is updated by Drupal\commerce_rental_reservation\EventSubscriber\RentalInstanceEventSubscriber
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the instance last came back from an order.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface $instance
   *   The rental instance.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(RentalInstanceInterface $instance) {
    $workflow = RentalInstanceType::load($instance->bundle())->getWorkflowId();
    return $workflow;
  }

  /**
   * Gets the rental instance selector plugin ID for the instance.
   *
   * @param \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface $instance
   *   The rental instance.
   *
   * @return string
   *   The rental instance selector plugin ID.
   */
  public static function getSelectorId(RentalInstanceInterface $instance) {
    $selector = RentalInstanceType::load($instance->bundle())->getSelectorId();
    return $selector;
  }
}
