<?php

namespace Drupal\Tests\auto_heading_ids;

use Drupal\Component\Utility\Html;
use Drupal\Core\Transliteration\PhpTransliteration;
use Drupal\Tests\UnitTestCase;
use Drupal\auto_heading_ids\Plugin\Filter\HeadingIdFilter;

/**
 * Tests the HeadingIdFilter class.
 *
 * @group auto_heading_ids
 * @coversDefaultClass \Drupal\auto_heading_ids\Plugin\Filter\HeadingIdFilter
 */
class HeadingIdFilterTest extends UnitTestCase {

  /**
   * Filter to test.
   *
   * @var Drupal\auto_heading_ids\Plugin\Filter\HeadingIdFilter
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $php_transliteration = new PhpTransliteration(NULL, $module_handler);
    $this->filter = new HeadingIdFilter([], '', ['provider' => 'provide'], $php_transliteration);

    // Required for resetting of getUniqueId.
    Html::resetSeenIds();
  }

  /**
   * Test the filterAttributes method.
   *
   * @dataProvider provideFilterAttributesCases
   */
  public function testFilterAttributes($test, $output, $match) {
    $filtered_output = $this->filter->filterAttributes($test);
    $this->assertEquals($output === $filtered_output, $match, $filtered_output);
  }

  /**
   * Provide test cases for filterAttributes test.
   */
  public function provideFilterAttributesCases() {
    $cases = [
      ['<h2>hello</h2>', '<h2 id="hello">hello</h2>', TRUE],
      ['<h3>hello</h3>', '<h3 id="hello">hello</h3>', TRUE],
      ['<h4>hello</h4>', '<h4 id="hello">hello</h4>', TRUE],
      ['<h5>hello</h5>', '<h5 id="hello">hello</h5>', TRUE],
      ['<h6>hello</h6>', '<h6 id="hello">hello</h6>', TRUE],
      ['<h1>hello</h1>', '<h1 id="hello">hello</h1>', FALSE],
      ['<h2 hello</h2>', '<h2 id="hello">hello</h2>', FALSE],
      [
        '<h2>Ä Ö Ü äöüåøhello</h2>',
        '<h2 id="a-o-u-aouaohello">Ä Ö Ü äöüåøhello</h2>',
        TRUE,
      ],
      // Note: this will create an empty id. To be fixed?
      ['<h2>👽</h2>', '<h2 id="">👽</h2>', TRUE],
      [
        '<h2>Here ia an extremely long heading for which a normal person would have nodded off before getting this far zzzzz oh is it done yet?</h2>',
        '<h2 id="here-ia-an-extremely-long-heading-for-which-a-normal-person-would-have-nodded-off-before-getting-this-far-zzzzz-oh-is-it-done">Here ia an extremely long heading for which a normal person would have nodded off before getting this far zzzzz oh is it done yet?</h2>',
        TRUE,
      ],
      [
        '<h2>hello</h2><h2>hello</h2>',
        '<h2 id="hello">hello</h2><h2 id="hello--2">hello</h2>',
        TRUE,
      ],
    ];

    return $cases;
  }

}
