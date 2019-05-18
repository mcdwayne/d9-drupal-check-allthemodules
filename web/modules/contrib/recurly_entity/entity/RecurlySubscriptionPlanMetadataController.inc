<?php

/**
 * @class RecurlySubscriptionPlanMetadataController
 * This describes our entity metadata using EntityAPI OOP-style notation instead
 * of function hooks. Less code in .modules, less code required per given page
 * load.
 */
class RecurlySubscriptionPlanMetadataController extends EntityDefaultMetadataController {

  /**
   * Overrides EntityDefaultMetadataController::entityPropertyInfo().
   */
  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $info[$this->type]['properties']['name'] = [
      'label' => t('Name'),
      'type' => 'string',
      'description' => t('Subscription name'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['plan_code'] = [
      'label' => t('Plan code'),
      'type' => 'string',
      'description' => t('Subscription plan code'),
      'schema field' => 'plan_code',
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['description'] = [
      'label' => t('Description'),
      'type' => 'string',
      'description' => t('Subscription plan subscription'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['display_donation_amounts'] = [
      'label' => t('Display donation amounts'),
      'type' => 'boolean',
      'description' => t('Subscription plan donation amounts'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['display_quantity'] = [
      'label' => t('Display quantity'),
      'type' => 'boolean',
      'description' => t('Subscription plan quantity'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['display_phone_number'] = [
      'label' => t('Display phone number'),
      'type' => 'boolean',
      'description' => t('Subscription plan phone number'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['bypass_hosted_confirmation'] = [
      'label' => t('Bypass hosted confirmation'),
      'type' => 'boolean',
      'description' => t('Subscription plan phone number'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['unit_name'] = [
      'label' => t('Unit name'),
      'type' => 'string',
      'description' => t('Subscription plan unit name'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['plan_interval_length'] = [
      'label' => t('Plan interval length'),
      'type' => 'integer',
      'description' => t('Subscription plan interval length'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['trial_interval_length'] = [
      'label' => t('Trial interval length'),
      'type' => 'integer',
      'description' => t('Subscription plan trial interval length'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['trial_interval_unit'] = [
      'label' => t('Trial interval unit'),
      'type' => 'string',
      'description' => t('Subscription plan trial unit name'),
      'setter callback' => 'entity_property_verbatim_set',
    ];
    $info[$this->type]['properties']['accounting_code'] = [
      'label' => t('Accounting code'),
      'type' => 'string',
      'description' => t('Subscription plan accounting code'),
      'setter callback' => 'entity_property_verbatim_set',
    ];

    return $info;
  }
}

