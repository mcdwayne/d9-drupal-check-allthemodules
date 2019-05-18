<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Tracking Category type, as defined at
 * https://developer.xero.com/documentation/api/tracking-categories
 *
 * @DataType(
 *   id = "xero_tracking",
 *   label = @Translation("Xero Tracking Category"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\TrackingCategoryDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList",
 * )
 */
class TrackingCategory extends XeroTypeBase {

  static public $guid_name = 'TrackingCategoryID';
  static public $xero_name = 'TrackingCategory';
  static public $plural_name = 'TrackingCategories';
  static public $label = 'Name';

}
