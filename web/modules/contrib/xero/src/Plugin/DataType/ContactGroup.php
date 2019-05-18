<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_contact_group",
 *   label = @Translation("Xero Contact Group"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\ContactGroupDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class ContactGroup extends XeroTypeBase {
  static public $guid_name = 'ContactGroupID';
  static public $xero_name = 'ContactGroup';
  static public $plural_name = 'ContactGroups';
  static public $label = 'Name';
}