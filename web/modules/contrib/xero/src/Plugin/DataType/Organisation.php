<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_organisation",
 *   label = @Translation("Xero Organization"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\OrganisationDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Organisation extends XeroTypeBase {
  static public $guid_name;
  static public $xero_name = 'Organisations';
  static public $plural_name = 'Organisation';
  static public $label = 'Name';
}