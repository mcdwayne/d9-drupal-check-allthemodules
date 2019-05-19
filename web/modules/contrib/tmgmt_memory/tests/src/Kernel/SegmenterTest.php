<?php

namespace Drupal\Tests\tmgmt_memory\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Unitary test for the Segmenter service.
 *
 * @group tmgmt_memory
 */
class SegmenterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'tmgmt', 'tmgmt_memory');

  /**
   * Test segmenting data.
   */
  public function testSegmentData() {
    $sample = file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_html/sample.html');
    $segmented_sample = file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_html/segmented_sample.html');
    /** @var \Drupal\tmgmt_memory\Segmenter $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');

    $segmented_data = $segmenter->getSegmentedData(['key' => [
      '#text' => $sample,
      '#translate' => TRUE,
    ]]);
    $this->assertEquals(str_replace(PHP_EOL, '', $segmented_sample), str_replace(PHP_EOL, '', $segmented_data['key']['#segmented_text']));
  }

  /**
   * Test filtering data.
   */
  public function testFilterData() {
    $segmented_sample = file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_html/segmented_sample.html');
    $filtered_sample = file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_html/sample.html');
    /** @var \Drupal\tmgmt_memory\Segmenter $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');

    $filtered_data = $segmenter->filterData($segmented_sample);
    $this->assertEquals(str_replace(PHP_EOL, '', $filtered_sample), $filtered_data);
  }

  /**
   * Test get segments of data.
   */
  public function testGetSegmentsOfData() {
    $segmented_sample = file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_html/segmented_sample.html');
    $expected = [
      1 => [
        'hash' => hash('sha256', 'Text not inside a paragraph.'),
        'id' => 1,
        'data' => 'Text not inside a paragraph.',
      ],
      2 => [
        'hash' => hash('sha256', '<p>one paragraph with special characters: äöüľščťžýáíéäňú©«®™»</p>'),
        'id' => 2,
        'data' => '<p>one paragraph with special characters: äöüľščťžýáíéäňú©«®™»</p>',
      ],
      3 => [
        'hash' => hash('sha256', '<p>one paragraph with a <br/>break line</p>'),
        'id' => 3,
        'data' => '<p>one paragraph with a <br/>break line</p>',
      ],
      4 => [
        'hash' => hash('sha256', '<p>one paragraph with html entities: &amp;&lt;&gt;</p>'),
        'id' => 4,
        'data' => '<p>one paragraph with html entities: &amp;&lt;&gt;</p>',
      ],
      5 => [
        'hash' => hash('sha256', '<p>and here we have some link <a href="http://example.com">break line</a></p>'),
        'id' => 5,
        'data' => '<p>and here we have some link <a href="http://example.com">break line</a></p>',
      ],
      6 => [
        'hash' => hash('sha256', '<p>one paragraph with an <img src="not-existing.gif" alt="not existing image" title="not existing image"/>image</p>'),
        'id' => 6,
        'data' => '<p>one paragraph with an <img src="not-existing.gif" alt="not existing image" title="not existing image"/>image</p>',
      ],
      7 => [
        'hash' => hash('sha256', '<p>hello <span class="green">world</span> this is <span class="red">simple html</span></p>'),
        'id' => 7,
        'data' => '<p>hello <span class="green">world</span> this is <span class="red">simple html</span></p>',
      ],
      8 => [
        'hash' => hash('sha256', '<div>nested 1</div>'),
        'id' => 8,
        'data' => '<div>nested 1</div>',
      ],
      9 => [
        'hash' => hash('sha256', '<div>nested 2</div>'),
        'id' => 9,
        'data' => '<div>nested 2</div>',
      ],
      10 => [
        'hash' => hash('sha256', '<div>nested 3</div>'),
        'id' => 10,
        'data' => '<div>nested 3</div>',
      ],
      11 => [
        'hash' => hash('sha256', 'more text'),
        'id' => 11,
        'data' => 'more text',
      ],
    ];
    /** @var \Drupal\tmgmt_memory\Segmenter $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');

    $segments = $segmenter->getSegmentsOfData($segmented_sample);
    $this->assertEquals($expected, $segments);
  }

}
