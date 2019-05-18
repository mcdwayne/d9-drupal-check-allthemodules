<?php

namespace Drupal\commerce_rental_reservation\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the rental reservation entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_rental_reservation",
 *   label = @Translation("Rental reservation"),
 *   label_collection = @Translation("Rental reservations"),
 *   label_singular = @Translation("rental reservation"),
 *   label_plural = @Translation("rental reservations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count rental reservation",
 *     plural = "@count rental reservations",
 *   ),
 *   bundle_label = @Translation("Rental reservation type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_rental",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   base_table = "commerce_rental_reservation",
 *   data_table = "commerce_rental_reservation_field_data",
 *   entity_keys = {
 *     "id" = "reservation_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/rental-reservation/{commerce_rental_reservation}",
 *     "delete-form" = "/admin/commerce/rental-reservation/{commerce_rental_reservation}/delete",
 *     "collection" = "/admin/commerce/rental-reservations"
 *   },
 *   bundle_entity_type = "commerce_rental_reservation_type",
 *   field_ui_base_route = "entity.commerce_rental_reservation_type.edit_form",
 * )
 */
class RentalReservation extends ContentEntityBase implements RentalReservationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  public function delete() {
    parent::delete();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    if ($order_item = $this->get('order_item_id')->entity) {
      $order = $order_item->getOrder();
      $order_item->delete();
      $order->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The order this reservation belongs to.'))
      ->setSetting('target_type', 'commerce_order')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['order_item_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order item'))
      ->setDescription(t('The order item.'))
      ->setSetting('target_type', 'commerce_order_item')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['period'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Rental Period'))
      ->setDescription(t('The rental reservation rental period'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product variation'))
      ->setDescription(t('The product variation'))
      ->setSetting('target_type', 'commerce_product_variation')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['instance'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Rental instance'))
      ->setDescription(t('The rental instance'))
      ->setSetting('target_type', 'commerce_rental_instance')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The rental reservation state.'))
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
      ->setSetting('workflow_callback', ['\Drupal\commerce_rental_reservation\Entity\RentalReservation', 'getWorkflowId']);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the reservation was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // This is updated by Drupal\commerce_rental_reservation\EventSubscriber\RentalReservationEventSubscriber
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the reservation was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_rental_reservation\Entity\RentalReservationInterface $reservation
   *   The rental reservation.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(RentalReservationInterface $reservation) {
    $workflow = RentalReservationType::load($reservation->bundle())->getWorkflowId();
    return $workflow;
  }
}
