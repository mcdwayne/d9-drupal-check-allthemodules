<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Ubercart billing profile source.
 *
 * @MigrateSource(
 *   id = "uc_profile_billing",
 *   source_module = "uc_order"
 * )
 */
class ProfileBilling extends DrupalSqlBase {

  /**
   * The join options between the uc_orders and uc_countries table.
   */
  const JOIN_COUNTRY = 'uc.country_id = uo.billing_country';

  /**
   * The join options between the uc_orders and uc_zones table.
   */
  const JOIN_ZONE = 'uz.zone_id = uo.billing_zone';

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Gets every order sorted by created date so the revisioning of the
    // billing profile is in the correct order.
    $query = $this->select('uc_orders', 'uo')->fields('uo')->orderBy('uo.created');
    $query->leftJoin('uc_countries', 'uc', static::JOIN_COUNTRY);
    $query->addField('uc', 'country_iso_code_2');
    $query->leftJoin('uc_zones', 'uz', static::JOIN_ZONE);
    $query->addField('uz', 'zone_code');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'order_id' => $this->t('Order ID'),
      'uid' => $this->t('User ID of order'),
      'order_status' => $this->t('Order status'),
      'order_total' => $this->t('Order total'),
      'product_count' => $this->t('Product count'),
      'primary_email' => $this->t('Email associated with order'),
      'delivery_first_name' => $this->t('Delivery first name'),
      'delivery_last_name' => $this->t('Delivery last name'),
      'delivery_phone' => $this->t('Delivery phone name'),
      'delivery_company' => $this->t('Delivery company name'),
      'delivery_street1' => $this->t('Delivery street address line 1'),
      'delivery_street2' => $this->t('Delivery street address line 2'),
      'delivery_city' => $this->t('Delivery city'),
      'delivery_zone' => $this->t('Delivery State'),
      'delivery_postal_code' => $this->t('delivery postal code'),
      'delivery_country' => $this->t('delivery country'),
      'billing_first_name' => $this->t('Billing first name'),
      'billing_last_name' => $this->t('Billing last name'),
      'billing_phone' => $this->t('Billing phone name'),
      'billing_company' => $this->t('Billing company name'),
      'billing_street1' => $this->t('Billing street address line 1'),
      'billing_street2' => $this->t('Billing street address line 2'),
      'billing_city' => $this->t('Billing city'),
      'billing_zone' => $this->t('Billing State'),
      'billing_postal_code' => $this->t('Billing postal code'),
      'billing_country' => $this->t('Billing country'),
      'payment_method' => $this->t('Payment method'),
      'data' => $this->t('Order attributes'),
      'created' => $this->t('Date/time of order creation'),
      'modified' => $this->t('Date/time of last order modification'),
      'host' => $this->t('IP address of customer'),
      'currency' => $this->t('Currency'),
      'status' => $this->t('Profile active.'),
      'is_default' => $this->t('Profile default.'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));
    // Determine if this is the last revision.
    $modified = (int) $row->getSourceProperty('modified');
    $uid = (int) $row->getSourceProperty('uid');

    $query = $this->select('uc_orders', 'uo')
      ->condition('uid', $uid)
      ->condition('modified', $modified, '>');
    $query->addExpression('COUNT(uo.uid)', 'count');
    $results = $query->execute()->fetchField();

    // If there are no more revisions then mark as active and as default.
    if ($results === '0') {
      $row->setSourceProperty('status', 1);
      $row->setSourceProperty('is_default', TRUE);
    }
    else {
      $row->setSourceProperty('status', 0);
      $row->setSourceProperty('is_default', NULL);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'order_id' => [
        'type' => 'integer',
        'alias' => 'uo',
      ],
    ];
  }

}
