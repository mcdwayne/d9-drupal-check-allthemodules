<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Alexanders Order entity.
 *
 * Mainly to a) remove commerce as a dependency and b) simplify management.
 *
 * @ContentEntityType(
 *   id = "alexanders_shipment",
 *   label = @Translation("Alexanders Shipment"),
 *   label_singular = @Translation("Alexanders Shipment"),
 *   label_plural = @Translation("Alexanders Shipments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count shipment",
 *     plural = "@count shipments",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *   },
 *   base_table = "alexanders_shipments",
 *   data_table = "alexanders_shipments_data",
 *   admin_permission = "administer site settings",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "shipment_id",
 *     "label" = "method",
 *   },
 * )
 */
class AlexandersShipment extends ContentEntityBase implements AlexandersShipmentInterface {

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->get('method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->set('method', $method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress() {
    return $this->get('address')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($address) {
    $this->address = $address;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTracking() {
    return $this->get('tracking')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTracking($number) {
    $this->set('tracking', $number);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($time) {
    $this->set('timestamp', $time);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCost() {
    return $this->get('cost')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCost($cost) {
    $this->set('cost', $cost);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function export() {
    $address = $this->getAddress()->getValue();
    $data = [
      'shipMethod' => $this->getMethod(),
      'address' => [
        'name' => $address['given_name'] . ' ' . $address['family_name'],
        'address1' => $address['address_line1'],
        'address2' => $address['address_line2'],
        'city' => $address['locality'],
        'state' => $address['administrative_area'],
        'postalCode' => $address['postal_code'],
        'countryCode' => $address['country_code'],
        'phoneNumber' => $address['phone_number'] ?? '0',
      ],
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['method'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setLabel(t('Method'))
      ->setDescription(t('Shipping method'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setRequired(TRUE)
      ->setLabel(t('Address'))
      ->setDescription(t('Shipping address'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['tracking'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tracking Number'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Time Shipped'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cost'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Shipping Cost'))
      ->setDisplayOptions('form', [
        'type' => 'number_float',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
