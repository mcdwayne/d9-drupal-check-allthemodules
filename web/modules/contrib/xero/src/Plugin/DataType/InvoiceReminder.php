<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * @DataType(
 *   id = "xero_invoice_reminder",
 *   label = @Translation("Xero Invoice Reminder"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\InvoiceReminderDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class InvoiceReminder extends XeroTypeBase {
  static public $guid_name;
  static public $xero_name = 'InvoiceReminder';
  static public $plural_name = 'InvoiceReminders';
  static public $label = 'Enabled';
}