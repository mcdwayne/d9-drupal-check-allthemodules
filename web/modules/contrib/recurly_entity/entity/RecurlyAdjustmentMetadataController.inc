<?php

/**
 * @class
 * Expose metadata from Adjustments to the entity API.
 */
class RecurlyAdjustmentMetadataController extends EntityDefaultMetadataController {

  /**
   * Overrides EntityDefaultMetadataController::entityPropertyInfo().
   */
  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info['recurly_adjustment']['properties'];
    $properties['created_at']['type'] = 'date';

    // Add a property for the total amount of an adjustment.
    $properties['total'] = [
      'label' => t('Total'),
      'description' => t('The total amount of this adjustment.'),
      'type' => 'integer',
      'getter callback' => 'recurly_entity_adjustment_total_get',
      'computed' => TRUE,
      'entity views field' => TRUE,
    ];

    return $info;
  }
}
