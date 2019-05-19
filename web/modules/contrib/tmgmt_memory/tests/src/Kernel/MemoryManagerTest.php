<?php

namespace Drupal\Tests\tmgmt_memory\Kernel;

use Drupal\Tests\tmgmt\Kernel\TMGMTKernelTestBase;

/**
 * Unitary test for the MemoryManager service.
 *
 * @group tmgmt_memory
 */
class MemoryManagerTest extends TMGMTKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'tmgmt_memory'];

  /**
   * Overrides KernelTestBase::setUp().
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('tmgmt_memory_segment');
    $this->installEntitySchema('tmgmt_memory_usage');
    $this->installEntitySchema('tmgmt_memory_segment_translation');
    $this->installEntitySchema('tmgmt_memory_usage_translation');
  }

  /**
   * Test adding segment.
   */
  public function testAddSegment() {
    $segmented_sample = file_get_contents(drupal_get_path('module', 'tmgmt_memory') . '/tests/testing_html/segmented_sample.html');
    /** @var \Drupal\tmgmt_memory\Segmenter $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');
    $segments = $segmenter->getSegmentsOfData($segmented_sample);
    $segment = reset($segments);
    $stripped_data = strip_tags($segment['data']);
    /** @var \Drupal\tmgmt_memory\MemoryManager $memory_manager */
    $memory_manager = \Drupal::service('tmgmt_memory.memory_manager');

    $saved_segment = $memory_manager->addSegment('en', $stripped_data);
    $this->assertEquals($stripped_data, $saved_segment->getStrippedData());
    // Check it has 0 usages.
    $this->assertEquals(0, $saved_segment->countUsages());
  }

  /**
   * Test adding usage.
   */
  public function testAddUsage() {
    $segment1 = '<p>one paragraph with a <br/>break line</p>';
    $segment2 = '<p><span>one paragraph with a </span><br/>break line</p>';
    $stripped_segment = 'one paragraph with a break line';

    /** @var \Drupal\tmgmt_memory\MemoryManager $memory_manager */
    $memory_manager = \Drupal::service('tmgmt_memory.memory_manager');

    // Add first usage.
    $usage = $memory_manager->addUsage('en', $segment1, 1, 'title|0|value', 1);
    $saved_segment = $usage->getSegment();
    $this->assertEquals($stripped_segment, $saved_segment->getStrippedData());
    // Check it has 1 usage.
    $this->assertEquals(1, $saved_segment->countUsages());

    // Add second usage.
    $usage = $memory_manager->addUsage('en', $segment2, 2, 'title|0|value', 1);
    $saved_segment = $usage->getSegment();
    $this->assertEquals($stripped_segment, $saved_segment->getStrippedData());
    // Check it has 2 usages.
    $this->assertEquals(2, $saved_segment->countUsages());
  }

  /**
   * Test adding segment translation.
   */
  public function testAddSegmentTranslation() {
    $source_segment = 'A paragraph in source language.';
    $target_segment = 'A paragraph in target language.';

    /** @var \Drupal\tmgmt_memory\MemoryManager $memory_manager */
    $memory_manager = \Drupal::service('tmgmt_memory.memory_manager');
    /** @var \Drupal\tmgmt_memory\SegmentTranslationInterface $translation */
    $translation = $memory_manager->addSegmentTranslation('en', $source_segment, 'ca', $target_segment);

    // Check source is saved.
    $source = $translation->getSource();
    $this->assertEquals($source_segment, $source->getStrippedData());

    // Check target is saved.
    $target = $translation->getTarget();
    $this->assertEquals($target_segment, $target->getStrippedData());
  }

  /**
   * Test the Workflow.
   */
  public function testWorkflow() {
    $this->doTestAddUsageTranslation();
    $this->doTestGetPerfectMatchForDataItem();
  }

  /**
   * Test adding usage translation.
   */
  public function doTestAddUsageTranslation() {
    /** @var \Drupal\tmgmt_memory\Segmenter $segmenter */
    $segmenter = \Drupal::service('tmgmt.segmenter');
    /** @var \Drupal\tmgmt_memory\MemoryManager $memory_manager */
    $memory_manager = \Drupal::service('tmgmt_memory.memory_manager');

    $job1 = $this->createJob('en', 'ca');
    \Drupal::state()->set('tmgmt.test_source_data', [
      'dummy' => [
        'deep_nesting' => [
          '#text' => '<p>First paragraph in source language.</p><p>Second paragraph in source language.</p>',
          '#label' => 'Label',
          '#translate' => TRUE,
        ],
      ],
    ]);
    $item1 = $job1->addItem('test_source', 'test', 1);
    $data = $item1->getData();
    $source_data_item = $data['dummy']['deep_nesting'];
    $source_segmented_data = $source_data_item['#segmented_text'];
    $source_segments = $segmenter->getSegmentsOfData($source_segmented_data);

    // Add translation.
    $translation['dummy']['deep_nesting']['#text'] = '<p>(ca) First paragraph in target language.</p><p>(ca) Second paragraph in target language.</p>';
    $item1->addTranslatedData($translation);
    $data = $segmenter->getSegmentedData($item1->getData());
    $target_data = $data['dummy']['deep_nesting']['#translation']['#text'];
    $target_segmented_data = $data['dummy']['deep_nesting']['#translation']['#segmented_text'];
    $target_segments = $segmenter->getSegmentsOfData($target_segmented_data);

    $job2 = $this->createJob('en', 'ca');
    \Drupal::state()->set('tmgmt.test_source_data', [
      'dummy' => [
        'deep_nesting' => [
          '#text' => '<p>First paragraph in <span>source language</span>.</p><p>Second paragraph in <span>source language</span>.</p>',
          '#label' => 'Label',
          '#translate' => TRUE,
        ],
      ],
    ]);
    $item2 = $job2->addItem('test_source', 'test', 1);
    $data = $item2->getData();
    $source_segmented_data2 = $data['dummy']['deep_nesting']['#segmented_text'];
    $source_segments2 = $segmenter->getSegmentsOfData($source_segmented_data2);

    // Add translation.
    $translation['dummy']['deep_nesting']['#text'] = '<p>(ca) First paragraph in <span>target language</span>.</p><p>(ca) Second paragraph in <span>target language</span>.</p>';
    $item2->addTranslatedData($translation);
    $data = $segmenter->getSegmentedData($item2->getData());
    $target_segmented_data2 = $data['dummy']['deep_nesting']['#translation']['#segmented_text'];
    $target_segments2 = $segmenter->getSegmentsOfData($target_segmented_data2);

    foreach ($source_segments as $delta => $source_segment) {
      $source_usage = $memory_manager->addUsage('en', $source_segment['data'], $item1->id(), 'data_item', $delta);
      $target_usage = $memory_manager->addUsage('ca', $target_segments[$delta]['data'], $item1->id(), 'data_item', $delta);
      $memory_manager->addUsageTranslation($source_usage, $target_usage);
      $source_usage2 = $memory_manager->addUsage('en', $source_segments2[$delta]['data'], $item2->id(), 'data_item', $delta);
      $target_usage2 = $memory_manager->addUsage('ca', $target_segments2[$delta]['data'], $item2->id(), 'data_item', $delta);
      $memory_manager->addUsageTranslation($source_usage2, $target_usage2);
    }

    $match = $memory_manager->getPerfectMatchForDataItem('en', 'ca', $source_data_item);
    $this->assertEquals($target_data, $match);

    // Check there is just 1 register in tmgmt_segment.
    /** @var \Drupal\tmgmt_memory\SegmentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment');
    $segments = $storage->loadMultipleByLanguageAndData('en', strip_tags($source_segments[1]['data']));
    $this->assertEquals(1, count($segments));

    // Check this segment has 2 usages.
    /** @var \Drupal\tmgmt_memory\SegmentInterface $segment */
    $segment = reset($segments);
    $this->assertEquals(2, $segment->countUsages());

    // Add a new translation.
    $target = '<tmgmt-segment id="1"><p>A paragraph in <span>alternative translation for target language</span>.</p></tmgmt-segment><tmgmt-segment id="2"><div>nested 1<tmgmt-segment id="3"><div>nested 2<tmgmt-segment id="4"><div>nested 3</div></tmgmt-segment></div></tmgmt-segment></div></tmgmt-segment>';
    $target_segments = $segmenter->getSegmentsOfData($target);
    $source_usage = $memory_manager->addUsage('en', $source_segments[1]['data'], $item2->id(), 'data_item', 1);
    $target_usage = $memory_manager->addUsage('ca', $target_segments[1]['data'], $item2->id(), 'data_item', 1);
    $memory_manager->addUsageTranslation($source_usage, $target_usage);

    // Check there is 2 registers in tmgmt_segment_translation.
    $results = $memory_manager->getSegmentTranslations('en', strip_tags($source_segments[1]['data']), 'ca');
    $this->assertEquals(2, count($results));

    // Check there is 1 register in tmgmt_usage_translation.
    $results = $memory_manager->getUsageTranslations('en', $source_segments[1]['data'], 'ca');
    $this->assertEquals(1, count($results));
  }

  /**
   * Test adding usage translation.
   */
  public function doTestGetPerfectMatchForDataItem() {
    /** @var \Drupal\tmgmt_memory\MemoryManager $memory_manager */
    $memory_manager = \Drupal::service('tmgmt_memory.memory_manager');

    $target_segments = '<p>(ca) First paragraph in <span>target language</span>.</p><p>(ca) Second paragraph in <span>target language</span>.</p>';

    // Create a new Job.
    $job2 = $this->createJob('en', 'ca');
    $item2 = $job2->addItem('test_source', 'test', 1);
    $source_data_item = $item2->getData(['dummy', 'deep_nesting']);
    $result = $memory_manager->getPerfectMatchForDataItem('en', 'ca', $source_data_item);
    $this->assertEquals($target_segments, $result);
  }

}
