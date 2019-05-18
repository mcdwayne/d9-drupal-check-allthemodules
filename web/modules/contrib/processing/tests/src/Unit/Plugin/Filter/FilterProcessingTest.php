<?php

namespace Drupal\Tests\processing\Unit\Plugin\Filter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\processing\Plugin\Filter\FilterProcessing;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Test the processing filter methods.
 *
 * @coversDefaultClass \Drupal\processing\Plugin\Filter\FilterProcessing
 *
 * @group processing
 */
class FilterProcessingTest extends UnitTestCase {

  /**
   * Processing Filter.
   *
   * @var \Drupal\processing\Plugin\Filter\FilterProcessing
   */
  protected $filter;

  /**
   * Translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->translation = $this->getStringTranslationStub();
    $renderer = $this->prophesize('\Drupal\Core\Render\RendererInterface');
    $renderer
      ->render(Argument::type('array'), FALSE)
      ->willReturn('<div class="item-list"><ul><li></li></li></ul></div>');

    $definition = [
      'id' => 'filter_processing',
      'type' => 'Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE',
      'provider' => 'processing',
      'settings' => [
        'blacklist' => 'println print link status param',
        'render_mode' => 'source',
      ],
    ];
    $conf = [
      'status' => 1,
      'weight' => 0,
      'settings' => $definition['settings'],
    ];

    $render_service = $renderer->reveal();
    $this->filter = new FilterProcessing($conf, 'filter_processing', $definition, $render_service);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->translation);
    $container->set('renderer', $render_service);
    \Drupal::setContainer($container);
  }

  /**
   * Test the settings form.
   *
   * @covers ::settingsForm
   */
  public function testSettingsForm() {
    $form = [];
    $form_state = new FormState();
    $form = $this->filter->settingsForm($form, $form_state);

    $this->assertArrayHasKey('render_mode', $form);
    $this->assertArrayHasKey('blacklist', $form);
  }

  /**
   * Test the tips.
   *
   * @covers ::tips
   */
  public function testTips() {

    $this->translation->expects($this->any())
      ->method('translate')
      ->willReturn(new TranslatableMarkup('Processing.js markup is enabled for this content', [], [], $this->translation));

    $this->assertEquals('Processing.js markup is enabled for this content.', $this->filter->tips());
  }

  /**
   * Test the tips.
   *
   * @covers ::tips
   */
  public function testTipsLong() {

    $this->translation->expects($this->any())
      ->method('translate')
      ->will($this->returnValueMap([
        ['Processing.js markup is enabled for this content.', new TranslatableMarkup('Processing.js markup is enabled for this content', [], [], $this->translation)],
        ['rendered sketches only', new TranslatableMarkup('rendered sketches only', [], [], $this->translation)],
        ['first', new TranslatableMarkup('first', [], [], $this->translation)],
        ['Your code is configured to display %mode.', new TranslatableMarkup('Your code is configured to display %mode', ['%mode' => 'rendered sketches only'], [], $this->translation)],
        ['The following functions are disabled, and will not be rendered:', new TranslatableMarkup('The following functions are disabled, and will not be rendered:', [], [], $this->translation)],
      ]));

    // This does not accurately cover all cases. A functional test is required
    // so that a full render is done.
    $this->assertRegExp('/^<div/', $this->filter->tips(TRUE));
  }

  /**
   * Test the prepare method in various scenarios.
   *
   * @param string $text
   *   The text to filter.
   * @param string $expected
   *   The expected output.
   *
   * @dataProvider prepareProvider
   *
   * @covers ::prepare
   */
  public function testPrepare($text, $expected) {
    $this->assertEquals($expected, $this->filter->prepare($text, ''));
  }

  /**
   * Test the process method in various scenarios.
   *
   * @param string $text
   *    The text to filter.
   * @param bool $should_have
   *    Whether the processed text should or should not have placeholder, which
   *    cannot be accurately mocked.
   *
   * @dataProvider processProvider
   *
   * @covers ::process
   */
  public function testProcess($text, $should_have) {
    $pattern = '/\<drupal-filter-placeholder callback="/';

    if ($should_have) {
      $this->assertRegExp($pattern, $this->filter->process($text, '')->getProcessedText());
    }
    else {
      $this->assertNotRegExp($pattern, $this->filter->process($text, '')->getProcessedText());
    }
  }

  /**
   * Provides data for text filter.
   *
   * @return array
   *   An array of parameters for the test method.
   */
  public function prepareProvider() {
    $ret = [];

    $noProcessing = "Multi-line text without any\nprocessing markup.";
    $noProcessingScript = "Multi-line text with
      JavaScript that should not be processed
      <script type=\"application/processing\">
        size(200, 200);
        background(102);
      </script>";
    $oneProcessingScript = "Multi-line text with
      JavaScript contained with processing block
      [processing]
        println('aaaa');
        size(200, 200);
        background(102);
      [/processing]";
    $oneExpected = "Multi-line text with
      JavaScript contained with processing block
      [processing]
        /* Restricted. println('aaaa'); */
        size(200, 200);
        background(102);
      [/processing]";
    $multiProcessingBlock = "Multi-line text with
      JavaScript contained within a processing block
      [processing]
        println('aaa');
        size(200, 200);
        background(102);
      [/processing]
      and JavaScript contained within a second block
      [processing]
        size(200, 200);
        println('aaa');
        background(200);
      [/processing]";
    $multiProcessingExpected = "Multi-line text with
      JavaScript contained within a processing block
      [processing]
        /* Restricted. println('aaa'); */
        size(200, 200);
        background(102);
      [/processing]
      and JavaScript contained within a second block
      [processing]
        size(200, 200);
        /* Restricted. println('aaa'); */
        background(200);
      [/processing]";

    $ret[] = [$noProcessing, $noProcessing];
    $ret[] = [$noProcessingScript, $noProcessingScript];
    $ret[] = [$oneProcessingScript, $oneExpected];
    $ret[] = [$multiProcessingBlock, $multiProcessingExpected];

    return $ret;
  }

  /**
   * Provides data for text filter.
   *
   * @return array
   *   An array of parameters for the test method.
   */
  public function processProvider() {
    $ret = [];

    $noProcessing = "Multi-line text without any\nprocessing markup.";
    $noProcessingScript = "Multi-line text with
      JavaScript that should not be processed
      <script type=\"application/processing\">
        size(200, 200);
        background(102);
      </script>";
    $oneProcessingScript = "Multi-line text with
      JavaScript contained with processing block
      [processing]
        size(200, 200);
        background(102);
      [/processing]";
    $multiProcessingBlock = "Multi-line text with
      JavaScript contained within a processing block
      [processing]
        size(200, 200);
        background(102);
      [/processing]
      and JavaScript contained within a second block
      [processing]
        size(200, 200);
        background(200);
      [/processing]";

    $ret[] = [$noProcessing, FALSE];
    $ret[] = [$noProcessingScript, FALSE];
    $ret[] = [$oneProcessingScript, TRUE];
    $ret[] = [$multiProcessingBlock, TRUE];

    return $ret;
  }

}
