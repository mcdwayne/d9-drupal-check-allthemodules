<?php

/**
 * @class RecurlySubscriptionMetadataController
 * This describes our entity metadata using EntityAPI OOP-style notation instead
 * of function hooks. Less code in .modules, less code required per given page
 * load.
 */
class RecurlySubscriptionMetadataController extends EntityDefaultMetadataController {

  /**
   * Overrides EntityDefaultMetadataController::entityPropertyInfo().
   */
  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info['recurly_subscription']['properties'];

    $properties['activated_at'] = [
      'label' => t('Activated at'),
      'type' => 'date',
      'description' => t('Date the subscription started'),
      'setter callback' => 'entity_property_verbatim_set',
      'getter callback' => 'recurly_entity_property_date_get',
      'entity views field' => TRUE,
    ];
    $properties['current_period_ends_at'] = [
      'label' => t('Current period ends at'),
      'type' => 'date',
      'description' => t('Current period ends at'),
      'setter callback' => 'entity_property_verbatim_set',
      'getter callback' => 'recurly_entity_property_date_get',
      'entity views field' => TRUE,
    ];
    $properties['unit_amount_in_cents'] = [
      'label' => t('Unit amount in cents'),
      'type' => 'integer',
      'description' => t('Unit amount of the subscription'),
      'setter callback' => 'entity_property_verbatim_set',
      'entity views field' => TRUE,
    ];

    return $info;
  }
}

