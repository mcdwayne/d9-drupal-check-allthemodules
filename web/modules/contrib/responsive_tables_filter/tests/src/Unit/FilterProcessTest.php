<?php

namespace Drupal\Tests\responsive_tables_filter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\filter\Plugin\Filter\FilterHtml;
use Drupal\responsive_tables_filter\Plugin\Filter\FilterResponsiveTablesFilter;

/**
 * @coversDefaultClass \Drupal\responsive_tables_filter\Plugin\Filter\FilterResponsiveTablesFilter
 * @group filter
 */
class FilterProcessTest extends UnitTestCase {
  /**
   * The filter_html class.
   *
   * @var \Drupal\responsive_tables_filter\Plugin\Filter\FilterResponsiveTablesFilter
   */
  protected $filter;

  /**
   * The filter_responsive_tables_filter class.
   *
   * @var \Drupal\responsive_tables_filter\Plugin\Filter\FilterResponsiveTablesFilter
   */
  protected $responsiveFilter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // First run text through the filter_html filter to simulate most likely
    // use case.
    $configuration['settings'] = [
      'allowed_html' => '<a href> <p> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <br> <h3 id> <table class additional> <th> <tr> <td> <thead> <tbody> <tfoot>',
      'filter_html_help' => 1,
      'filter_html_nofollow' => 0,
      'filter_responsive_tables_filter' => ["tablesaw_type" => "stack"],
    ];
    $this->filter = new FilterHtml($configuration, 'filter_html', ['provider' => 'test']);
    $this->filter->setStringTranslation($this->getStringTranslationStub());

    // See Drupal\Core\Plugin\PluginBase.
    $this->responsiveFilter = new FilterResponsiveTablesFilter([], 'filter_responsive_tables_filter', ['provider' => 'test']);
  }

  /**
   * @covers ::runFilter
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
    $result = $this->responsiveFilter->runFilter($html_filter);
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
      ['<table></table>', '<table class="tablesaw tablesaw-stack" data-tablesaw-mode="stack" data-tablesaw-minimap=""></table>'],
      ['<table class="test"></table>', '<table class="test tablesaw tablesaw-stack" data-tablesaw-mode="stack" data-tablesaw-minimap=""></table>'],
      ['<table class="no-tablesaw"></table>', '<table class="no-tablesaw"></table>'],
      ['<table additional="test"><thead><tr><th>Header One<th>Header 2<tbody><tr><td>Easily add tables with the WYSIWYG toolbar<td>Encoded characters test öô & , ?<tr><td>Tables respond to display on smaller screens<td>Fully accessible to screen readers</table>', '<table additional="test" class="tablesaw tablesaw-stack" data-tablesaw-mode="stack" data-tablesaw-minimap="">
<thead><tr>
<th>Header One</th>
<th>Header 2</th>
</tr></thead>
<tbody>
<tr>
<td>Easily add tables with the WYSIWYG toolbar</td>
<td>Encoded characters test öô &amp; , ?</td>
</tr>
<tr>
<td>Tables respond to display on smaller screens</td>
<td>Fully accessible to screen readers</td>
</tr>
</tbody>
</table>',
      ],
    ];
  }

}
