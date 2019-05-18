<?php
/**
 * @file
 * xero.theme.inc
 */

use Drupal\Core\Template\Attribute;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Template preprocess for xero reference field item.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_reference(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);

  $variables['guid'] = in_array('guid', $variables['settings']['display']) ? $variables['item']->guid : FALSE;
  $variables['label'] = in_array('label', $variables['settings']['display']) ? $variables['item']->label : FALSE;
  $variables['type'] = $variables['item']->type;
  $variables['type_label'] = in_array('type', $variables['settings']['display']) ? $variables['definition']['label'] : FALSE;
}

/**
 * Template preprocess for xero_account.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_account(&$variables) {
  $variables['attributes']['class'][] = strtolower($variables['item']['Status']);

  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_bank_transaction.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_bank_transaction(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_contact.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_contact(&$variables) {
  $variables['attributes']['class'][] = $variables['item']['ContactStatus'];
  $variables['attributes']['itemscope'] = NULL;
  $variables['attributes']['itemtype'] = 'http://schema.org/Person';

  $variables['name'] = '';
  if ($variables['item']['FirstName']) {
    $variables['name'] .= $variables['item']['FirstName'];
  }
  if ($variables['item']['LastName']) {
    $variables['name'] .= empty($variables['name']) ? $variables['item']['LastName'] : ' ' . $variables['item']['LastName'];
  }

  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_credit_note.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_credit_note(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_employee.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_employee(&$variables) {
  $variables['attributes']['itemtype'] = 'http://schema.org/Person';
  $variables['attributes']['itemscope'] = NULL;
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_expense.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_expense(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_invoice.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_invoice(&$variables) {
  $variables['attributes']['itemscope'] = NULL;
  $variables['attributes']['itemtype'] = 'http://schema.org/Invoice';

  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_item.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_item(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_journal.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_journal(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_payment.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_payment(&$variables) {
  $variables['title'] = t('Payment of Invoice @value to @name for @amount on @date', [
    '@value' => $variables['item']['Invoice']['InvoiceNumber'],
    '@name' => $variables['item']['Invoice']['Contact']['Name'],
    '@amount' => $variables['item']['Amount'],
    '@date' => $variables['item']['Date']
  ]);

  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_receipt.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_receipt(&$variables) {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Template preprocess for xero_user.
 *
 * @param &$variables
 *   The variables array.
 */
function template_preprocess_xero_user(&$variables) {
  $variables['attributes']['itemscope'] = NULL;
  $variables['attributes']['itemtype'] = 'http://schema.org/Person';

  $variables['attributes'] = new Attribute($variables['attributes']);
}