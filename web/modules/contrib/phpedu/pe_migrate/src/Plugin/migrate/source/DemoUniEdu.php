<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniEdu.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_edu"
 * )
 */
class DemoUniEdu extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_edu', 'pene')
      ->fields('pene', ['title', 'body', 'path', 'field_image', 'field_founding_date', 'field_defunct_date', 'field_institution_type', 'field_history', 'field_main_location', 'field_code', 'field_address__streetAddress', 'field_address__addressLocality', 'field_address__addressRegion', 'field_address__postalCode', 'field_address__addressCountry', 'field_telephone_number', 'field_fax_number', 'field_url__url', 'field_email', 'field_legal_name', 'field_duns_number', 'field_vat_id', 'field_tax_id'])
      ->orderBy('title', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'body' => $this->t('Description'),
      'path' => $this->t('Path'),
      'field_image' => $this->t('Image'),
      'field_founding_date' => $this->t('Founding date'),
      'field_defunct_date' => $this->t('Defunct date'),
      'field_institution_type' => $this->t('Instituition type'),
      'field_history' => $this->t('History'),
      'field_main_location' => $this->t('Main location'),
      'field_code' => $this->t('Code'),
      'field_address__streetAddress' => $this->t('Address - Street'),
      'field_address__addressLocality' => $this->t('Address - City'),
      'field_address__addressRegion' => $this->t('Address - Region'),
      'field_address__postalCode' => $this->t('Address - Postcode'),
      'field_address__addressCountry' => $this->t('Address - Country'),
      'field_telephone_number' => $this->t('Telephone number'),
      'field_fax_number' => $this->t('Fax number'),
      'field_url__url' => $this->t('Website'),
      'field_email' => $this->t('Email'),
      'field_legal_name' => $this->t('Legal name'),
      'field_duns_number' => $this->t('DUNS number'),
      'field_vat_id' => $this->t('VAT ID'),
      'field_tax_id' => $this->t('Tax ID'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'title' => [
        'type' => 'string',
        'alias' => 'pene',
      ],
    ];
  }

}
