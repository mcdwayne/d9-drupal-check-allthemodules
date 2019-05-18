<?php

namespace Drupal\Tests\iframe_title_filter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\filter\Plugin\Filter\FilterHtml;
use Drupal\iframe_title_filter\Plugin\Filter\FilteriFrameTitle;

/**
 * @coversDefaultClass \Drupal\iframe_title_filter\Plugin\Filter\FilteriFrameTitle
 * @group filter
 */
class FilterTest extends UnitTestCase {
  /**
   * The filter_html class.
   *
   * @var \Drupal\filter\Plugin\Filter\FilterHtml
   */
  protected $filter;

  /**
   * The filter_responsive_tables_filter class.
   *
   * @var \Drupal\iframe_title_filter\Plugin\Filter\FilteriFrameTitle
   */
  protected $responsive_filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // First run text through the filter_html filter to simulate most likely
    // use case.
    $configuration['settings'] = [
      'allowed_html' => '<iframe src title width height><a href> <p> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <br> <h3 id> <table class additional> <th> <tr> <td> <thead> <tbody> <tfoot>',
      'filter_html_help' => 1,
      'filter_html_nofollow' => 0,
    ];
    $this->filter = new FilterHtml($configuration, 'filter_html', ['provider' => 'test']);
    $this->filter->setStringTranslation($this->getStringTranslationStub());

    // See Drupal\Core\Plugin\PluginBase.
    $this->responsive_filter = new FilteriFrameTitle(array(), 'filter_iframe_title', ['provider' => 'test']);
  }

  /**
   * @covers ::process
   *
   * @dataProvider providerFilterAttributes
   *
   * @param string $html
   *   Input HTML.
   * @param string $expected
   *   The expected output string.
   */
  public function testfilterAttributes($html, $expected) {
    $html_filter = $this->filter->filterAttributes($html);
    $result = $this->responsive_filter->process($html_filter, NULL)->getProcessedText();
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for testfilterAttributes.
   *
   * @return array
   *   An array of test data.
   */
  public function providerFilterAttributes() {
    return [
      // Adds a title when none present, using the url host.
      ['<iframe src="https://test.com"></iframe>', '<iframe src="https://test.com" title="Embedded content from test.com"></iframe>'],
      // Works with single quotes (converts).
      ["<iframe src='https://test.com'></iframe>", "<iframe src=\"https://test.com\" title=\"Embedded content from test.com\"></iframe>"],
      // An existing title is not be overwritten.
      ['<iframe src="https://test.com" title="An existing title"></iframe>', '<iframe src="https://test.com" title="An existing title"></iframe>'],
      // Other attributes are preserved.
      ['<iframe src="https://html.com" width="100%" height="500"></iframe>', '<iframe src="https://html.com" width="100%" height="500" title="Embedded content from html.com"></iframe>'],
    ];
  }

}
