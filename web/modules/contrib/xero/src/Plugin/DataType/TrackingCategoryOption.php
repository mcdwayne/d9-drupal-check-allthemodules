<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_tracking_category_option",
 *   label = @Translation("Tracking Category Option"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\TrackingCategoryOptionDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class TrackingCategoryOption extends XeroTypeBase {

  static public $guid_name = 'TrackingOptionID';
  static public $xero_name = 'Option';
  static public $plural_name = 'Options';
  static public $label = 'Name';

}