<?php

namespace Drupal\shortcode\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\shortcode\Shortcode\ShortcodeService;

/**
 * Tests the Drupal 8 shortcode module functionality.
 *
 * @group shortcode
 */
class ShortcodeTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['filter', 'shortcode'];

  /**
   * The shortcode service.
   *
   * @var \Drupal\shortcode\Shortcode\ShortcodeService
   */
  private $shortcodeService;

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->shortcodeService = \Drupal::service('shortcode');
    $this->siteUrl = Url::fromRoute('<front>', [], ["absolute" => TRUE])->toString();
  }

  /**
   * Tests that the Button shortcode returns the right content.
   */
  public function testButtonShortcode() {

    $sets = [
      [
        'input' => '[button]Label[/button]',
        'output' => '<a href="' . $this->siteUrl . '" class="button" title="Label"><span>Label</span></a>',
        'message' => 'Button shortcode output matches.',
      ],
      [
        'input' => '[button path="<front>" class="custom-class"]Label[/button]',
        'output' => '<a href="' . $this->siteUrl . '" class="custom-class button" title="Label"><span>Label</span></a>',
        'message' => 'Button shortcode with custom class output matches.',
      ],
      [
        'input' => '[button path="http://www.google.com" class="custom-class" title="Title" id="theLabel" style="border-radius:5px;"]Label[/button]',
        'output' => '<a href="http://www.google.com" class="custom-class button" id="theLabel" style="border-radius:5px;" title="Title"><span>Label</span></a>',
        'message' => 'Button shortcode with custom attributes and absolute output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the Clear shortcode returns the right content.
   */
  public function testClearShortcode() {

    $sets = [
      [
        'input' => '[clear]<div>Other elements</div>[/clear]',
        'output' => '<div class="clearfix"><div>Other elements</div></div>',
        'message' => 'Clear shortcode output matches.',
      ],
      [
        'input' => '[clear type="s"]<div>Other elements</div>[/clear]',
        'output' => '<span class="clearfix"><div>Other elements</div></span>',
        'message' => 'Clear shortcode with custom type "s" output matches.',
      ],
      [
        'input' => '[clear type="span"]<div>Other elements</div>[/clear]',
        'output' => '<span class="clearfix"><div>Other elements</div></span>',
        'message' => 'Clear shortcode with custom type "span" output matches.',
      ],
      [
        'input' => '[clear type="d"]<div>Other elements</div>[/clear]',
        'output' => '<div class="clearfix"><div>Other elements</div></div>',
        'message' => 'Clear shortcode with custom type "d" output matches.',
      ],
      [
        'input' => '[clear type="d" class="custom-class" id="theLabel" style="background-color: #F00;"]<div>Other elements</div>[/clear]',
        'output' => '<div class="custom-class clearfix" id="theLabel" style="background-color: #F00;"><div>Other elements</div></div>',
        'message' => 'Clear shortcode with custom attributes output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the Dropcap shortcode returns the right content.
   */
  public function testDropcapShortcode() {

    $sets = [
      [
        'input' => '[dropcap]text[/dropcap]',
        'output' => '<span class="dropcap">text</span>',
        'message' => 'Dropcap shortcode output matches.',
      ],
      [
        'input' => '[dropcap class="custom-class"]text[/dropcap]',
        'output' => '<span class="custom-class dropcap">text</span>',
        'message' => 'Dropcap shortcode with custom class output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the highlight shortcode returns the right content.
   */
  public function testHighlightShortcode() {

    $test_input = '[highlight]highlighted text[/highlight]';
    $expected_output = '<span class="highlight">highlighted text</span>';
    $output = $this->shortcodeService->process($test_input);
    $this->assertEqual($output, $expected_output, 'Highlight shortcode output matches.');

    $test_input = '[highlight class="custom-class"]highlighted text[/highlight]';
    $expected_output = '<span class="custom-class highlight">highlighted text</span>';
    $output = $this->shortcodeService->process($test_input);
    $this->assertEqual($output, $expected_output, 'Highlight shortcode with custom class output matches.');
  }

  /**
   * Tests that the Image shortcode returns the right content.
   */
  public function testImgShortcode() {

    $sets = [
      [
        'input' => '[img src="/abc.jpg" alt="Test image" /]',
        'output' => '<img src="/abc.jpg" class="img" alt="Test image"/>',
        'message' => 'Image shortcode output matches.',
      ],
      [
        'input' => '[img src="/abc.jpg" class="custom-class" alt="Test image" /]',
        'output' => '<img src="/abc.jpg" class="custom-class img" alt="Test image"/>',
        'message' => 'Image shortcode with custom class output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the Item shortcode returns the right content.
   */
  public function testItemShortcode() {

    $sets = [
      [
        'input' => '[item]Item body here[/item]',
        'output' => '<div>Item body here</div>',
        'message' => 'Item shortcode output matches.',
      ],
      [
        'input' => '[item type="s"]Item body here[/item]',
        'output' => '<span>Item body here</span>',
        'message' => 'Item shortcode with custom type "s" output matches.',
      ],
      [
        'input' => '[item type="d" class="item-class-here" style="background-color:#F00"]Item body here[/item]',
        'output' => '<div class="item-class-here" style="background-color:#F00">Item body here</div>',
        'message' => 'Item shortcode with custom type "d" and class and styles output matches.',
      ],
      [
        'input' => '[item type="s" class="item-class-here" style="background-color:#F00"]Item body here[/item]',
        'output' => '<span class="item-class-here" style="background-color:#F00">Item body here</span>',
        'message' => 'Item shortcode with custom type "s" and class and styles output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the Link shortcode returns the right content.
   */
  public function testLinkShortcode() {

    $sets = [
      [
        'input' => '[link path="node/1"]Label[/link]',
        'output' => '<a href="' . $this->siteUrl . 'node/1" title="Label">Label</a>',
        'message' => 'Link shortcode output matches.',
      ],
      [
        'input' => '[link path="node/23" title="Google" class="link-class" style="background-color:#0FF;"] Label [/link]',
        'output' => '<a href="' . $this->siteUrl . 'node/23" class="link-class" style="background-color:#0FF;" title="Google"> Label </a>',
        'message' => 'Link shortcode with title and attributes output matches.',
      ],
      [
        'input' => '[link url="http://google.com" title="Google" class="link-class" style="background-color:#0FF;"] Label [/link]',
        'output' => '<a href="http://google.com" class="link-class" style="background-color:#0FF;" title="Google"> Label </a>',
        'message' => 'Link shortcode with url, title and attributes output matches.',
      ],
      [
        'input' => '[link path="node/23" url="http://google.com" title="Google" class="link-class" style="background-color:#0FF;"] Label [/link]',
        'output' => '<a href="http://google.com" class="link-class" style="background-color:#0FF;" title="Google"> Label </a>',
        'message' => 'Link shortcode with both path and url, title and attributes output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the Quote shortcode returns the right content.
   */
  public function testQuoteShortcode() {

    $sets = [
      [
        'input' => '[quote]This is by no one[/quote]',
        'output' => '<span class="quote"> This is by no one </span>',
        'message' => 'Quote shortcode output matches.',
      ],
      [
        'input' => '[quote class="test-quote"]This is by no one[/quote]',
        'output' => '<span class="test-quote quote"> This is by no one </span>',
        'message' => 'Quote shortcode with class output matches.',
      ],
      [
        'input' => '[quote class="test-quote" author="ryan"]This is by ryan[/quote]',
        'output' => '<span class="test-quote quote"> <span class="quote-author">ryan wrote: </span> This is by ryan </span>',
        'message' => 'Quote shortcode with class and author output matches.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $output = preg_replace('/\s+/', ' ', $output);
      $this->assertEqual($output, $set['output'], $set['message']);
    }
  }

  /**
   * Tests that the Random shortcode returns the right length.
   */
  public function testRandomShortcode() {

    $sets = [
      [
        'input' => '[random/]',
        'output' => 8,
        'message' => 'Random shortcode self-closed, output length is correct.',
      ],
      [
        'input' => '[random][/random]',
        'output' => 8,
        'message' => 'Random shortcode output, length is correct.',
      ],
      [
        'input' => '[random length=10][/random]',
        'output' => 10,
        'message' => 'Random shortcode with custom length, output length is correct.',
      ],
    ];

    foreach ($sets as $set) {
      $output = $this->shortcodeService->process($set['input']);
      $this->assertEqual(strlen($output), $set['output'], $set['message']);
    }
  }

}
