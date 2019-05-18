<?php

namespace Drupal\Tests\editor\Kernel;

use Drupal\file\Entity\File;
use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests OSS image style filter.
 *
 * @group ossfs
 */
class FilterOssStyleTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'filter',
    'file',
    'user',
    'ossfs',
  ];

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installSchema('ossfs', ['ossfs_file']);

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the filter.
   */
  public function testFilter() {
    $filter = $this->filters['filter_oss_style'];
    $filter->setConfiguration([
      'settings' => [
        'style' => 'oss_thumb',
      ]
    ]);

    $test = function ($input) use ($filter) {
      return $filter->process($input, 'und');
    };

    $image = File::create(['uri' => 'oss://abc.jpg']);
    $image->save();
    $uuid = $image->uuid();

    $image_2 = File::create(['uri' => 'public://def.jpg']);
    $image_2->save();
    $uuid_2 = $image_2->uuid();

    // No data-entity-type and no data-entity-uuid attribute.
    $input = '<img src="abc.jpg" />';
    $output = $test($input);
    $this->assertEquals($input, $output->getProcessedText());

    // A non-file data-entity-type attribute value.
    $input = '<img src="abc.jpg" data-entity-type="invalid-entity-type-value" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertEquals($input, $output->getProcessedText());

    // One data-entity-uuid attribute.
    $input = '<img src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<img src="abc.jpg?x-oss-process=style/oss_thumb" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertEquals($expected_output, $output->getProcessedText());

    // One data-entity-uuid attribute with odd capitalization.
    $input = '<img src="abc.jpg" data-entity-type="file" DATA-entity-UUID =   "' . $uuid . '" />';
    $output = $test($input);
    $expected_output = '<img src="abc.jpg?x-oss-process=style/oss_thumb" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $this->assertEquals($expected_output, $output->getProcessedText());

    // One data-entity-uuid attribute on a non-image tag.
    $input = '<video src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<video src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '"></video>';
    $output = $test($input);
    $this->assertEquals($expected_output, $output->getProcessedText());

    // One data-entity-uuid attribute with an invalid value.
    $input = '<img src="abc.jpg" data-entity-type="file" data-entity-uuid="invalid-' . $uuid . '" />';
    $output = $test($input);
    $this->assertEquals($input, $output->getProcessedText());

    // Two different data-entity-uuid attributes.
    $input = '<img src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $input .= '<img src="def.jpg" data-entity-type="file" data-entity-uuid="' . $uuid_2 . '" />';
    $expected_output = '<img src="abc.jpg?x-oss-process=style/oss_thumb" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output .= '<img src="def.jpg" data-entity-type="file" data-entity-uuid="' . $uuid_2 . '" />';
    $output = $test($input);
    $this->assertEquals($expected_output, $output->getProcessedText());

    // Two identical data-entity-uuid attributes.
    $input =  '<img src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $input .= '<img src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output = '<img src="abc.jpg?x-oss-process=style/oss_thumb" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $expected_output .= '<img src="abc.jpg?x-oss-process=style/oss_thumb" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertEquals($expected_output, $output->getProcessedText());

    // Set empty style.
    $filter->setConfiguration([
      'settings' => [
        'style' => '',
      ]
    ]);

    $input = '<img src="abc.jpg" data-entity-type="file" data-entity-uuid="' . $uuid . '" />';
    $output = $test($input);
    $this->assertEquals($input, $output->getProcessedText());
  }

}
