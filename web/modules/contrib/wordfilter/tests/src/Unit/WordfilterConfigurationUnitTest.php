<?php

namespace Drupal\Tests\wordfilter\Unit;

use \Drupal\Tests\UnitTestCase;
use \Drupal\wordfilter\Entity\WordfilterConfiguration;
use \Drupal\wordfilter\Plugin\WordfilterProcess\DefaultWordfilterProcess;

/**
 * @coversDefaultClass \Drupal\wordfilter\Entity\WordfilterConfiguration
 * @group filter
 */
class WordfilterConfigurationUnitTest extends UnitTestCase {

  /**
   * @var \Drupal\wordfilter\Entity\WordfilterConfiguration
   */
  protected $wordfilter_config = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->wordfilter_config = new WordfilterConfiguration([
      'id' => 'my_superduper_wordfilter_configuration',
      'label' => 'My superduper Wordfilter configuration'],
      'wordfilter_configuration');
  }

  /**
   * @covers ::getProcess
   * @covers ::setProcess
   */
  public function testProcess() {
    $config = $this->wordfilter_config;
    $process = new DefaultWordfilterProcess([], 'default', []);
    $config->setProcess($process);
    $this->assertEquals($process, $config->getProcess());
  }

  /**
   * @covers ::getItems
   * @covers ::newItem
   * @covers ::removeItem
   */
  public function testItems() {
    $config = $this->wordfilter_config;
    $items = $config->getItems();
    // Always expect at least one item.
    $this->assertEquals(1, count($items));
    $config->removeItem(reset($items));
    $this->assertEquals(1, count($config->getItems()));

    // Add one item.
    $config->newItem();
    $items = $config->getItems();
    $this->assertEquals(2, count($items));

    // Manipulate and reset the first item.
    $items = $config->getItems();
    $item = reset($items);
    $item->setSubstitute('Lorem');
    $items = $config->getItems();
    $item = reset($items);
    $this->assertEquals('Lorem', $item->getSubstitute());
    $config->newItem($item->getDelta());
    $items = $config->getItems();
    $item = reset($items);
    // No new item.
    $this->assertEquals(2, count($items));
    // Resetting the item leads to empty values.
    $this->assertEquals('', $item->getSubstitute());

    // Remove the first item.
    $items = $config->getItems();
    $item = reset($items);
    $config->removeItem($item);
    $this->assertEquals(1, count($config->getItems()));
  }
}
