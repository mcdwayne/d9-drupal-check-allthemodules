<?php

/**
 *
 * Implements hook_entity_property_info
 * 
 */
function drealty_entity_property_info() {
  $info = array();
  $properties = &$info['drealty_listing']['properties'];

  $properties['id'] = array(
    'label' => t('Drealty Listing Id'),
    'type' => 'integer',
    'description' => t('The unique ID of the drealty listing'),
    'schema field' => 'id',
  );
  $properties['type'] = array(
    'label' => t('Drealty Listing Type'),
    'type' => 'text',
    'description' => t('The drealty property type'),
    'schema field' => 'type',
  );
  $properties['hash'] = array(
    'label' => t('Drealty hash'),
    'type' => 'text',
    'description' => t('The crc32 hash value of the listing data.'),
    'schema field' => 'hash',
    'getter callback' => 'entity_property_verbatim_get',
    'setter callback' => 'entity_property_verbatim_set',
  );
  $properties['rets_key'] = array(
    'label' => t('Drealty Listing RETS Key'),
    'type' => 'text',
    'description' => t('The RETS System Unique ID for the drealty listing'),
    'schema field' => 'rets_key',
  );
  $properties['rets_id'] = array(
    'label' => t('Drealty Listing RETS ID'),
    'type' => 'text',
    'description' => t('The RETS Public Unique ID for the drealty listing'),
    'schema field' => 'rets_id',
  );
  $properties['label'] = array(
    'label' => t('Drealty Listing Label'),
    'type' => 'text',
    'description' => t('The drealty entity label.'),
    'schema field' => 'label',
  );
  $properties['active'] = array(
    'label' => t('Drealty Active Status'),
    'type' => 'integer',
    'description' => t('The active status of the listing. 1 being active, 0 inactive.'),
    'schema field' => 'active',
    'getter callback' => 'entity_property_verbatim_get',
    'setter callback' => 'entity_property_verbatim_set',
  );
  $properties['inactive_date'] = array(
    'label' => t('Drealty Inactive Date'),
    'type' => 'integer',
    'description' => t('The date a listing was set to inactive by the import.'),
    'schema field' => 'inactive_date',
    'getter callback' => 'entity_property_verbatim_get',
    'setter callback' => 'entity_property_verbatim_set',
  );
  return $info;
}