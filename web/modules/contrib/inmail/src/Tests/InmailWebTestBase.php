<?php

namespace Drupal\inmail\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\Tests\inmail\Kernel\InmailTestHelperTrait;

/**
 * Provides common helper methods for Inmail web tests.
 *
 * @group inmail
 * @requires module past_db
 */
abstract class InmailWebTestBase extends WebTestBase {

  use DelivererTestTrait, InmailTestHelperTrait;

  /**
   * The Inmail processor service.
   *
   * @var \Drupal\inmail\MessageProcessor
   */
  protected $processor;

  /**
   * The Inmail parser service.
   *
   * @var \Drupal\inmail\MIME\MimeParser
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user and log in.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer inmail',
    ]);
    $this->drupalLogin($user);

    // Set the Inmail processor and parser services.
    $this->processor = \Drupal::service('inmail.processor');
    $this->parser = \Drupal::service('inmail.mime_parser');
  }

  /**
   * Process the raw test mail message.
   *
   * @param string $raw_message
   *   The raw mail message to be processed.
   */
  protected function processRawMessage($raw_message) {
    $deliverer = $this->createTestDeliverer();
    $this->processor->process('unique_key', $raw_message, $deliverer);
  }

  /**
   * Passes if the identification field IS found on the loaded page.
   *
   * @param string $label
   *   The label of the identification field.
   * @param string $value
   *   (optional) The content of the identification field.
   * Defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertIdentificationField($label, $value = NULL, $message = '') {
    return $this->assertIdentificationFieldHelper($label, $value, $message, TRUE);
  }

  /**
   * Passes if the identification field is NOT found on the loaded page.
   *
   * @param string $label
   *   The label of the identification field.
   * @param string $value
   *   (optional) The content of the identification field.
   * Defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoIdentificationField($label, $value = NULL, $message = '') {
    return $this->assertIdentificationFieldHelper($label, $value, $message, FALSE);
  }

  /**
   * Helper for assertIdentificationField and assertNoIdentificationField.
   *
   * @param string $label
   *   The label of the identification field element.
   * @param string $value
   *   (optional) The content of the identification field element.
   * Defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this identification field should exist, else FALSE.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertIdentificationFieldHelper($label, $value = NULL, $message = '', $exists = TRUE) {
    $xpath_pre = $this->getIdentificationFieldPreXpath();
    $raw_html_thread = $label . ': ' . htmlentities($value);

    if (!$message) {
      $message = $this->getFormattedMessage($label, $value, $exists);
    }

    if ($exists) {
      $this->assertTrue(strpos($xpath_pre, $raw_html_thread), $message);
    }
    else {
      $this->assertFalse(strpos($xpath_pre, $raw_html_thread), $message);
    }
  }

  /**
   * Passes if the rendered header field IS found on the loaded page.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param string $display_name
   *   (optional) The address display name of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertAddressHeaderField($label, $email_address = NULL, $display_name = NULL, $index = 0, $message = '') {
    return $this->assertAddressHeaderFieldHelper($label, $email_address, $display_name, $index, $message, TRUE);
  }

  /**
   * Passes if the rendered header field is NOT found on the loaded page.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param string $display_name
   *   (optional) The address display name of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoAddressHeaderField($label, $email_address = NULL, $display_name = NULL, $index = 0, $message = '') {
    return $this->assertAddressHeaderFieldHelper($label, $email_address, $display_name, $index, $message, FALSE);
  }

  /**
   * Helper for assertAddressHeaderField and assertNoAddressHeaderField.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param string $display_name
   *   (optional) The address display name of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this header field should exist, FALSE otherwise.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertAddressHeaderFieldHelper($label, $email_address = NULL, $display_name = NULL, $index = 0, $message = '', $exists = TRUE) {
    $xpath_div = $this->getHeaderFieldDivXpath($label);
    $label_xpath = $this->getHeaderFieldLabelXpath($xpath_div);

    if (!$message) {
      $message = $this->getFormattedMessage('label', $label, $exists);
    }

    if ($exists) {
      $this->assertFieldByXPath($label_xpath, $label, $message);
      if ($display_name) {
        $this->assertAddressDisplayName($label, $email_address, $display_name, $index);
      }
      else {
        $this->assertEmailAddress($label, $email_address, $index);
      }
    }
    else {
      $this->assertNoFieldByXPath($label_xpath, $label, $message);
      if ($display_name) {
        $this->assertNoAddressDisplayName($label, $email_address, $display_name, $index);
      }
      else {
        $this->assertNoEmailAddress($label, $email_address, $index);
      }
    }
  }

  /**
   * Passes if the header field address display name IS found on the page.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param string $display_name
   *   (optional) The address display name of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertAddressDisplayName($label, $email_address = NULL, $display_name = NULL, $index = 0, $message = '') {
    $this->assertAddressDisplayNameHelper($label, $email_address, $display_name, $index, $message, TRUE);
  }

  /**
   * Passes if the header field address display name is NOT found on the page.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param string $display_name
   *   (optional) The address display name of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoAddressDisplayName($label, $email_address = NULL, $display_name = NULL, $index = 0, $message = '') {
    $this->assertAddressDisplayNameHelper($label, $email_address, $display_name, $index, $message, FALSE);
  }

  /**
   * Helper for assertAddressDisplayName and assertNoAddressDisplayName.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param string $display_name
   *   (optional) The address display name of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this header field should exist, FALSE otherwise.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertAddressDisplayNameHelper($label, $email_address = NULL, $display_name = NULL, $index = 0, $message = '', $exists = TRUE) {
    $xpath_div = $this->getHeaderFieldDivXpath($label);
    $title_email_address_xpath = $this->getHeaderFieldTitleEmailAddressXpath($xpath_div, $email_address);
    $display_name_xpath = $this->getHeaderFieldDisplayNameXpath($xpath_div, $index);

    if (!$message) {
      $message = $this->getFormattedMessage('display name', $display_name, $exists);
    }

    if ($exists) {
      $this->assertRaw($title_email_address_xpath);
      $this->assertTrue(strpos($display_name_xpath, $display_name), $message);
    }
    else {
      if ($display_name) {
        $this->assertNull($title_email_address_xpath);
        $this->assertNull($display_name_xpath, $message);
      }
      else {
        $this->assertEmailAddress($label, $email_address, $index);
      }
    }
  }

  /**
   * Passes if the header field email address IS found on the page.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertEmailAddress($label, $email_address = NULL, $index = 0, $message = '') {
    $this->assertEmailAddressHelper($label, $email_address, $index, $message, TRUE);
  }

  /**
   * Passes if the header field email address is NOT found on the page.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoEmailAddress($label, $email_address = NULL, $index = 0, $message = '') {
    $this->assertEmailAddressHelper($label, $email_address, $index, $message, FALSE);
  }

  /**
   * Helper for assertEmailAddress and assertNoEmailAddress.
   *
   * @param string $label
   *   The label of the header field address.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *   Defaults to NULL.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this header field should exist, FALSE otherwise.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertEmailAddressHelper($label, $email_address = NULL, $index = 0, $message = '', $exists = TRUE) {
    $xpath_div = $this->getHeaderFieldDivXpath($label);
    $email_address_xpath = $this->getHeaderFieldEmailAddressXpath($xpath_div, $index);

    if (!$message) {
      $message = $this->getFormattedMessage('email address', $email_address, $exists);
    }

    if ($exists) {
      $this->assertTrue(strpos($email_address_xpath, $email_address), $message);
    }
    else {
      $this->assertNull($email_address_xpath, $message);
    }
  }

  /**
   * Passes if the header field element IS found on the page.
   *
   * @param string $label
   *   The label of the header field element.
   * @param string $value
   *   (optional) The content of the header field element. Defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertElementHeaderField($label, $value = NULL, $message = '') {
    $this->assertElementHeaderFieldHelper($label, $value, $message, TRUE);
  }

  /**
   * Passes if the header field element is NOT found on the page.
   *
   * @param string $label
   *   The label of the header field element.
   * @param string $value
   *   (optional) The content of the header field element. Defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoElementHeaderField($label, $value = NULL, $message = '') {
    $this->assertElementHeaderFieldHelper($label, $value, $message, FALSE);
  }

  /**
   * Helper for assertElementHeaderField and assertNoElementHeaderField.
   *
   * @param string $label
   *   The label of the header field element.
   * @param string $value
   *   (optional) The content of the header field element. Defaults to NULL.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this header field should exist, FALSE otherwise.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertElementHeaderFieldHelper($label, $value = NULL, $message = '', $exists = TRUE) {
    $xpath_div = $this->getHeaderFieldDivXpath($label);
    $label_xpath = $this->getHeaderFieldLabelXpath($xpath_div);
    $value_xpath = $this->getHeaderFieldElementValueXpath($xpath_div);

    $label_message = $message ?: $this->getFormattedMessage('label', $label, $exists);
    $value_message = $this->getFormattedMessage($label . ' value', $value, $exists);

    if ($exists) {
      $this->assertFieldByXPath($label_xpath, $label, $label_message);
      $this->assertTrue(strpos($value_xpath, $value), $value_message);
    }
    else {
      $this->assertNoFieldByXPath($label_xpath, $label, $label_message);
      $this->assertNull($value_xpath, $value_message);
    }
  }

  /**
   * Gets the 'Unsubscribe' Xpath.
   *
   * @todo implement assertUnsubscribeHeaderField()/assertNoUnsubscribeHeaderField(),
   *       improve assertUnsubscribeHeaderFieldHelper() and getHeaderFieldUnsubscribeXpath() ?
   *
   * @param string $xpath
   *   The specific header field element Xpath where the 'Unsubscribe' is.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return string
   *   The display name Xpath of the specific header field element.
   */
  private function assertHeaderFieldEmailUnsubscribe($xpath, $message = '') {
    $xpath = $this->getHeaderFieldUnsubscribeXpath($xpath);
    $xpath_href = $this->xpath($xpath)[0]->attributes();
    // RFC2369 defines header List-Unsubscribe.
    // List-Unsubscribe: <mailto:unsubscribe-espc-tech-12345N@domain.com>,
    // <http://domain.com/member/unsubscribe/?listname=espc-tech@domain.com?id=12345N>
    // We only identify http links and skip mailto.
    $this->assertFieldsByValue($xpath_href[0], 'http://domain.com/member/unsubscribe/?listname=espc-tech@domain.com?id=12345N');
    $this->assertFieldByXPath($xpath, 'Unsubscribe', $message);
  }

  /**
   * Asserts a raw mail body.
   *
   * @param string $machine_name
   *   The machine name to identify the value.
   * @param string $value
   *   The content of the raw body element.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE in case there is the raw email body present. Otherwise, FALSE.
   */
  protected function assertRawBody($machine_name, $value, $message = '') {
    return $this->assertRawBodyHelper($machine_name, $value, $message, TRUE);
  }

  /**
   * Asserts a raw mail body does not exist.
   *
   * @param string $machine_name
   *   The machine name to identify the value.
   * @param string $value
   *   (optional) The content of the raw body element.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE in case there is not the raw email body present. Otherwise, FALSE.
   */
  protected function assertNoRawBody($machine_name, $value = '', $message = '') {
    return $this->assertRawBodyHelper($machine_name, $value, $message, FALSE);
  }

  /**
   * Helper for assertRawBody and assertNoRawBody.
   *
   * @param string $machine_name
   *   The machine name to identify the value.
   * @param string $value
   *   (optional) The content of the raw body element.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this header field should exist, FALSE otherwise.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE in case there is a raw email body present. Otherwise, FALSE.
   */
  protected function assertRawBodyHelper($machine_name, $value = '', $message = '', $exists = TRUE) {
    $div_xpath = $this->getBodyDivXpath($machine_name);
    $message_values = ['@machine_name' => $machine_name, '@value' => $value];

    if ($exists) {
      $value_message = $message ?: new FormattableMarkup('@machine_name body message "@value" found.', $message_values);
      return $this->assertTrue(strpos($div_xpath[0]->asXML(), $value) !== FALSE, $value_message);
    }
    else {
      $value_message = $message ?: new FormattableMarkup('@machine_name body message "@value" not found.', $message_values);
      return $this->assertTrue(empty($div_xpath), $value_message);
    }
  }

  /**
   * Gets the 'All Headers' field 'pre' Xpath.
   *
   * @return string|null
   *   The 'All Headers' field 'pre' Xpath as a string, NULL otherwise.
   */
  private function getIdentificationFieldPreXpath() {
    $xpath = '//div[@class="inmail-message__element inmail-message__header__all"]/pre';
    return $this->xpath($xpath) ? $this->xpath($xpath)[0]->asXML() : NULL;
  }

  /**
   * Gets the header field 'div' Xpath.
   *
   * @param string $label
   *   The label of the header field element to assert.
   *
   * @return string
   *   The header field 'div' Xpath as a string.
   */
  private function getHeaderFieldDivXpath($label) {
    $field = $label;
    if ($label == 'reply to') {
      $field = 'reply-to';
    }
    elseif ($label == 'Received') {
      $field = 'received-date';
    }
    $xpath_div = '//div[@class="inmail-message__element inmail-message__header__' . strtolower($field) . '"]';

    return $xpath_div;
  }

  /**
   * Gets the label Xpath of a specific header field element.
   *
   * @param string $xpath
   *   The specific header field element Xpath where to get the label from.
   *
   * @return string
   *   The label Xpath of the specific header field element.
   */
  private function getHeaderFieldLabelXpath($xpath) {
    return $xpath . '/label';
  }

  /**
   * Gets the email address Xpath of a specific header field element.
   *
   * @param string $xpath
   *   The specific header field element Xpath where the email address is.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   *
   * @return string|null
   *   The email address Xpath of the specific header field element, else NULL.
   */
  private function getHeaderFieldEmailAddressXpath($xpath, $index = 0) {
    $xpath .= '/span[contains(@class, "inmail-message__address")]';
    $xpath .= ($index > 0) ? '[' . $index . ']' : '';
    return $this->xpath($xpath) ? $this->xpath($xpath)[0]->asXML() : NULL;
  }

  /**
   * Gets the email address Xpath in a span element as title attribute.
   *
   * @param string $xpath
   *   The specific header field element Xpath where the email address is.
   * @param string $email_address
   *   (optional) The email address of the header field address.
   *
   * @return string|null
   *   The title value Xpath of the specific header field element, else NULL.
   */
  private function getHeaderFieldTitleEmailAddressXpath($xpath, $email_address = '') {
    $xpath .= '/span[contains(@title, "' . $email_address . '")]';
    return $this->xpath($xpath) ? $this->xpath($xpath)[0]->asXML() : NULL;
  }

  /**
   * Gets the display name Xpath of a specific header field element.
   *
   * @param string $xpath
   *   The specific header field element Xpath where the display name is.
   * @param int $index
   *   (optional) Span position counting from 1. Defaults to 0.
   *
   * @return string|null
   *   The display name Xpath of the specific header field element, else NULL.
   */
  private function getHeaderFieldDisplayNameXpath($xpath, $index = 0) {
    $xpath .= '/span[contains(@class, "inmail-message__address__display-name")]';
    $xpath .= ($index > 0) ? '[' . $index . ']' : '';
    return $this->xpath($xpath) ? $this->xpath($xpath)[0]->asXML() : NULL;
  }

  /**
   * Gets the 'Unsubscribe' Xpath.
   *
   * @param string $xpath
   *   The specific header field element Xpath where the 'Unsubscribe' is.
   *
   * @return string
   *   The display name Xpath of the specific header field element.
   */
  private function getHeaderFieldUnsubscribeXpath($xpath) {
    return $xpath . '/a';
  }

  /**
   * Gets the 'Date' or 'Subject' content Xpath.
   *
   * @param string $xpath
   *   The specific header field element Xpath where to get the value form.
   *
   * @return string|null
   *   The content value Xpath of the specific header field element, else NULL.
   */
  private function getHeaderFieldElementValueXpath($xpath) {
    return $this->xpath($xpath) ? $this->xpath($xpath)[0]->__toString() : NULL;
  }

  /**
   * Gets the XPath of the mail body message element.
   *
   * @param string $machine_name
   *   The machine name to identify the value.
   *
   * @return \SimpleXMLElement[]|bool
   *   The XPath of the mail body message element or FALSE on failure.
   */
  private function getBodyDivXpath($machine_name) {
    $field = $machine_name;
    if ($machine_name == 'HTML') {
      $field = 'html';
    }
    elseif ($machine_name == 'Plain') {
      $field = 'content';
    }
    $xpath_div = '//div[contains(@class, "inmail-message__element inmail-message__body__' . strtolower($field) . '")]';

    return $this->xpath($xpath_div);
  }

  /**
   * Gets the message to be used for the assertion.
   *
   * @param string $machine_name
   *   The machine name to identify the value.
   * @param string $value
   *   The value to the asserted.
   * @param bool $exists
   *   (optional) TRUE if this header field should exist, FALSE otherwise.
   *   Defaults to TRUE.
   *
   * @return string
   *   The message to be used for the assertion.
   */
  private function getFormattedMessage($machine_name = '', $value = '', $exists = TRUE) {
    $message_values = ['@machine_name' => $machine_name, '@value' => $value];
    if ($exists) {
      $message = new FormattableMarkup('Header field @machine_name "@value" found.', $message_values);
    }
    else {
      $message = new FormattableMarkup('Header field @machine_name "@value" not found.', $message_values);
    }

    return $message;
  }

}
