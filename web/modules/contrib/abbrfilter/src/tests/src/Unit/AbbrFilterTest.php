<?php
namespace Drupal\Tests\abbrfilter\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the functionality of the abbrfilter module.
 */
class AbbrfilterTest extends UnitTestCase {

  /**
   * Injected \Drupal\abbrfilter\AbbrFilterData service.
   */
  protected $abbrfilterData;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $methods = get_class_methods('Drupal\abbrfilter\AbbrfilterData');
    unset($methods[array_search('perform_subs', $methods)]);
    $this->abbrfilterData = $this->getMockBuilder('Drupal\abbrfilter\AbbrfilterData')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
  }
  
  /**
   * Test the HTML parsing.
   */
  function testAbbrFilter() {
    $html_in = $this->getTestText('input');
    $expected_output = $this->getTestText('output');

    $list = $this->getFilterList();

    $output = $this->abbrfilterData->perform_subs($html_in, $list);

    $this->assertSame($output, $expected_output);
  }

  /**
   * Set filter list.
   *
   * @return array
   *   Filter list.
   */
  protected function getFilterList() {
    return [
      0 => [
        'id' => 1,
        'abbrs' => 'VHS',
        'replacement' => 'Video Home System',
      ],
      1 => [
        'id' => 2,
        'abbrs' => 'PBR',
        'replacement' => 'Pabst Blue Ribbon',
      ],
      2 => [
        'id' => 3,
        'abbrs' => 'TB-IO',
        'replacement' => 'This bar is over',
      ],
      3 => [
        'id' => 4,
        'abbrs' => 'TCP/IP',
        'replacement' => 'Transmission control protocol, internet protocol',
      ],
    ];
  }

  /**
   * Helper function to read the sample test files.
   * 
   * @param $op
   *   input/output option
   * 
   * @return string
   *   Content of the test files.
   */
  protected function getTestText($op) {
    return file_get_contents(__DIR__ . '/sample-' . $op . '.html');
  }
}
