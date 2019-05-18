<?php

namespace Drupal\Tests\inmail\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behaviour of Inmail Message element.
 *
 * @group inmail
 * @requires module past_db
 */
class InmailMessageTest extends KernelTestBase {

  use InmailTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail_test',
    'inmail',
    'system',
  ];

  /**
   * The parser service.
   *
   * @var \Drupal\inmail\MIME\MimeParserInterface
   */
  protected $parser;

  /**
   * The message decomposition service.
   *
   * @var \Drupal\Inmail\MIME\MimeMessageDecomposition
   */
  protected $messageDecomposition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['inmail', 'system']);
    $this->parser = \Drupal::service('inmail.mime_parser');
    $this->messageDecomposition = \Drupal::service('inmail.message_decomposition');
  }

  /**
   * Tests the html-only preprocess body message.
   */
  public function testProcessHtmlOnly() {
    // Get the body paths for the processed html-only message.
    $message = $this->parser->parseMessage($this->getMessageFileContents('/simple/html-text.eml'));
    $body_paths = $this->messageDecomposition->getBodyPaths($message);
    // Assert the html/plain body paths.
    $this->assertEquals('~', $body_paths['html']);
    $this->assertNull($body_paths['plain']);

    // Get the renderer arrays as strings for both full/teaser view modes.
    $build_full['email'] = [
      '#type' => 'inmail_message',
      '#message' => $message,
      '#view_mode' => 'full',
    ];
    $build_teaser['email'] = [
      '#type' => 'inmail_message',
      '#message' => $message,
      '#view_mode' => 'teaser',
    ];
    $rendered_full = \Drupal::service('renderer')->renderRoot($build_full);
    $rendered_teaser = \Drupal::service('renderer')->renderRoot($build_teaser);

    // Check the html body for full view mode.
    $this->assertHtmlElement($rendered_full, '<a href="#inmail-message__body__html">HTML</a>');
    $this->assertHtmlElement($rendered_full, '<a href="#inmail-message__body__content">Plain</a>');
    $this->assertHtmlElement($rendered_full, '<div class="inmail-message__element inmail-message__body__content" id="inmail-message__body__content">');
    $this->assertHtmlElement($rendered_full, '<div class="inmail-message__element inmail-message__body__html" id="inmail-message__body__html"><div dir="ltr">');
    $this->assertHtmlElement($rendered_full, '<p>Hey Alice,</p>');
    $this->assertHtmlElement($rendered_full, '<p>Skype told me its your birthday today? Congratulations!</p>');
    $this->assertHtmlElement($rendered_full, '<p>Wish I could be there and celebrate with you...</p>');
    $this->assertHtmlElement($rendered_full, '<p>Sending you virtual cake, french cheese and champagne (without alcohol, just for you!) :P</p>');
    $this->assertHtmlElement($rendered_full, '<p>Cheerious,</p>');
    $this->assertHtmlElement($rendered_full, '<p>Bob</p>');
    $this->assertHtmlElement($rendered_full, '</div>');

    // Check the plain body for teaser view mode.
    $this->assertNoHtmlElement($rendered_teaser, '<div class="inmail-message__element inmail-message__body__html" id="inmail-message__body__html">');
    $this->assertHtmlElement($rendered_teaser, '<div class="inmail-message__element inmail-message__body__content" id="inmail-message__body__content">Hey Alice,');
    $this->assertHtmlElement($rendered_teaser, 'Skype told me its your birthday today? Congratulations!');
    $this->assertHtmlElement($rendered_teaser, 'Wish I could be there and celebrate with you...');
    $this->assertHtmlElement($rendered_teaser, 'Sending you virtual cake, french cheese and champagne (without alcohol, just for you!) :P');
    $this->assertHtmlElement($rendered_teaser, 'Cheerious,');
    $this->assertHtmlElement($rendered_teaser, 'Bob</div>');
  }

  /**
   * Passes if the HTML element is found in the rendered array.
   *
   * @param string $renderer
   *   The renderer array as a string.
   * @param string $element
   *   The element string to look for in the rendered array.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  protected function assertHtmlElement($renderer, $element, $message = '') {
    $this->assertHtmlElementHelper($renderer, $element, $message, TRUE);
  }

  /**
   * Passes if the HTML element is NOT found in the rendered array.
   *
   * @param string $renderer
   *   The renderer array as a string.
   * @param string $element
   *   The element string to look for in the rendered array.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  protected function assertNoHtmlElement($renderer, $element, $message = '') {
    $this->assertHtmlElementHelper($renderer, $element, $message, FALSE);
  }

  /**
   * Helper for assertHtmlElement and assertNoHtmlElement.
   *
   * @param string $renderer
   *   The renderer array as a string.
   * @param string $element
   *   The element string to look for in the rendered array.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $exists
   *   (optional) TRUE if this element should exist, FALSE if it should not.
   *   Defaults to TRUE.
   */
  protected function assertHtmlElementHelper($renderer, $element = '', $message = '', $exists = TRUE) {
    $element = Xss::filterAdmin($element);

    if (!$message) {
      if ($exists) {
        $message = new FormattableMarkup('Element "@element" found.', ['@element' => $element]);
      }
      else {
        $message = new FormattableMarkup('Element "@element" not found.', ['@element' => $element]);
      }
    }

    if ($exists) {
      $this->assertTrue(strpos($renderer, $element), $message);
    }
    else {
      $this->assertFalse(strpos($renderer, $element), $message);
    }
  }

}
