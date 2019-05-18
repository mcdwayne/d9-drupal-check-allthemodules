<?php

namespace Drupal\Tests\adobe_analytics\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests AdobeAnalyticsHelper.
 *
 * @group adobe_analytics
 *
 * @coversDefaultClass \Drupal\adobe_analytics\AdobeAnalyticsHelper
 */
class AdobeAnalyticsHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'adobe_analytics',
    'adobe_analytics_test',
    'system',
    'user',
  ];

  /**
   * An instance of the AdobeAnalyticsHelper service.
   *
   * @var \Drupal\adobe_analytics\AdobeAnalyticsHelper
   */
  protected $adobeAnalyticsHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('menu');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installConfig('adobe_analytics');
    $this->adobeAnalyticsHelper = $this->container->get('adobe_analytics.adobe_analytics_helper');
  }

  /**
   * Tests the constructor by simply checking the created service.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->assertNotNull($this->adobeAnalyticsHelper);
  }

  /**
   * Tests retrieving token contexts.
   *
   * @covers ::adobeAnalyticsGetTokenContext
   */
  public function testAdobeAnalyticsGetTokenContext() {
    $context = $this->adobeAnalyticsHelper->adobeAnalyticsGetTokenContext();
    $this->assertNotEmpty($context);
  }

  /**
   * Tests token replacement.
   *
   * @param string $input_string
   *   The input string.
   * @param string $expected_string
   *   The expected string.
   *
   * @covers ::adobeAnalyticsTokenReplace
   *
   * @dataProvider adobeAnalyticsTokenReplaceProvider
   */
  public function testAdobeAnalyticsTokenReplace($input_string, $expected_string) {
    $string_processed = $this->adobeAnalyticsHelper->adobeAnalyticsTokenReplace($input_string);
    $this->assertEquals($expected_string, $string_processed);
  }

  /**
   * Data provider for ::testAdobeAnalyticsTokenReplace()
   */
  public function adobeAnalyticsTokenReplaceProvider() {
    return [
      ['foo', 'foo'],
      ['', ''],
    ];
  }

  /**
   * Test variable setter and getter.
   *
   * @covers ::setVariable
   * @covers ::getVariables
   */
  public function testVariableGetterSetter() {
    $this->adobeAnalyticsHelper->setVariable('foo', 'bar');
    $this->assertEquals(['foo' => 'bar'], $this->adobeAnalyticsHelper->getVariables());
  }

  /**
   * Tests variable formatting.
   *
   * @covers ::adobeAnalyticsFormatVariables
   */
  public function testAdobeAnalyticsFormatVariables() {
    $this->adobeAnalyticsHelper->setVariable('foo', 'bar');
    $formatted_variables = $this->adobeAnalyticsHelper->adobeAnalyticsFormatVariables([
      'foo' => [
        'pow',
        'bang',
      ],
    ]);
    $this->assertEquals("foo=\"bar\";\n", $formatted_variables);
  }

  /**
   * Tests logic to use or skip tracking.
   *
   * @covers ::skipTracking
   */
  public function testSkipTracking() {
    \Drupal::configFactory()->getEditable('adobe_analytics.settings')
      ->set('track_roles', [
        'anonymous' => 'anonymous',
        'authenticated' => '0',
        'administrator' => '0',
      ])
      ->set('role_tracking_type', 'inclusive')
      ->save();
    $this->assertFalse($this->adobeAnalyticsHelper->skipTracking());

    \Drupal::configFactory()->getEditable('adobe_analytics.settings')
      ->set('role_tracking_type', 'exclusive')
      ->save();
    $this->assertTrue($this->adobeAnalyticsHelper->skipTracking());
  }

  /**
   * Tests markup rendering.
   *
   * @covers ::renderMarkup
   *
   * @see \adobe_analytics_test_adobe_analytics_variables()
   */
  public function testRenderMarkup() {
    // Check empty markup when skipTracking returns FALSE.
    \Drupal::configFactory()->getEditable('adobe_analytics.settings')
      ->set('track_roles', [
        'anonymous' => 'anonymous',
      ])
      ->set('role_tracking_type', 'exclusive')
      ->save();
    $this->assertEquals([], $this->adobeAnalyticsHelper->renderMarkup());

    // Check markup when skipTracking returns TRUE.
    \Drupal::configFactory()->getEditable('adobe_analytics.settings')
      ->set('js_file_location', 'http://www.example.com/js/s_code_remote_h.js')
      ->set('image_file_location', 'http://examplecom.112.2O7.net/b/ss/examplecom/1/H.20.3--NS/0')
      ->set('version', 'H.20.3.')
      ->set('track_roles', [
        'anonymous' => 'anonymous',
        'authenticated' => '0',
        'administrator' => '0',
      ])
      ->set('role_tracking_type', 'inclusive')
      ->set('codesnippet', 'foo="bar";')
      ->save();

    $expected = [
      '#theme' => 'analytics_code',
      '#js_file_location' => 'http://www.example.com/js/s_code_remote_h.js',
      '#version' => 'H.20.3.',
      '#image_location' => 'http://examplecom.112.2O7.net/b/ss/examplecom/1/H.20.3--NS/0',
      '#formatted_vars' => "boom=\"pow\";\nfoo=\"bar\";\nslap=\"twist\";\nsmash=\"crash\";\n",
    ];
    $this->assertEquals($expected, $this->adobeAnalyticsHelper->renderMarkup());
  }

}
