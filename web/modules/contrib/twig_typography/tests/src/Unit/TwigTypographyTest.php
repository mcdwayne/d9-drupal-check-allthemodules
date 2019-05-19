<?php

namespace Drupal\Tests\twig_typography\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\twig_typography\TwigExtension\TestTypography;

/**
 * Tests the twig extension.
 *
 * @group twig_typography
 * @group Template
 */
class TwigTypographyTest extends UnitTestCase {

  /**
   * The system under test.
   *
   * @var \Drupal\Core\Template\TwigExtension
   */
  protected $systemUnderTest;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->systemUnderTest = new TestTypography();
  }

  /**
   * Tests the php-typography filter Widows functionality.
   */
  public function testTypographyWidows() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test page title</h1>');
    $this->assertSame('<h1 class="page-title">Test page&nbsp;title</h1>', $this->systemUnderTest->applyTypography($string_object, []));
  }

  /**
   * Tests whether a single parameter passed to applyTypography().
   *
   * In this test the widows are disabled and so the string should remain
   * the same.
   */
  public function testTypographyWidowsDisabled() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test page title</h1>');
    $this->assertSame('<h1 class="page-title">Test page title</h1>', $this->systemUnderTest->applyTypography($string_object, ['set_dewidow' => FALSE]));
  }

  /**
   * Tests whether an array parameter passed to applyTypography().
   *
   * In this test the page-title class is set to ignored so the widow function
   * should not apply within it.
   */
  public function testTypographyIgnoreClass() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test page title</h1>');
    $this->assertSame('<h1 class="page-title">Test page title</h1>', $this->systemUnderTest->applyTypography($string_object, ['set_classes_to_ignore' => ['page-title']]));
  }

  /**
   * Tests the php-typography filter curly quotes functionality.
   */
  public function testTypographyQuotes() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test "the" page \'title\'</h1>');
    $this->assertSame('<h1 class="page-title">Test <span class="push-double"></span>​<span class="pull-double">“</span>the” page <span class="push-single"></span>​<span class="pull-single">‘</span>title’</h1>', $this->systemUnderTest->applyTypography($string_object, []));
  }

  /**
   * Tests the php-typography filter curly quotes functionality without hanging.
   */
  public function testTypographyQuotesNoHanging() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test "the" page \'title\'</h1>');
    $this->assertSame('<h1 class="page-title">Test “the” page ‘title’</h1>', $this->systemUnderTest->applyTypography($string_object, ['set_style_hanging_punctuation' => FALSE]));
  }

  /**
   * Tests settings reset when FALSE is passed as second parameter.
   */
  public function testTypographySettingsReset() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test page title</h1>');
    $this->assertSame('<h1 class="page-title">Test page title</h1>', $this->systemUnderTest->applyTypography($string_object, [], FALSE));
  }

  /**
   * Tests enabling dewidow when settings have been reset.
   */
  public function testTypographySettingsResetWithWidows() {
    $string_object = new TwigExtensionTestString('<h1 class="page-title">Test page title</h1>');
    $this->assertSame('<h1 class="page-title">Test page&nbsp;title</h1>', $this->systemUnderTest->applyTypography($string_object, [
      'set_dewidow' => TRUE,
      'set_dewidow_word_number' => 1,
      'set_max_dewidow_length' => 5,
      'set_max_dewidow_pull' => 5,
    ], FALSE));
  }

}

/**
 * Creates an object which can be cast to a string.
 *
 * Mimics an object produced by the twig render function.
 *
 * @package Drupal\Tests\twig_typography\Unit
 */
class TwigExtensionTestString {

  protected $string;

  /**
   * TwigExtensionTestString constructor.
   *
   * @param string $string
   *   The string to hold.
   */
  public function __construct($string) {
    $this->string = $string;
  }

  /**
   * When cast to a string this passes the string back.
   *
   * @return string
   *   Returns the string.
   */
  public function __toString() {
    return $this->string;
  }

}
