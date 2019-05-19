<?php

namespace Drupal\Tests\wordfilter\Unit;

use \Drupal\Tests\UnitTestCase;
use \Drupal\wordfilter\Entity\WordfilterConfiguration;
use \Drupal\wordfilter\Plugin\WordfilterProcess\DefaultWordfilterProcess;

/**
 * @coversDefaultClass \Drupal\wordfilter\Plugin\WordfilterProcess\DefaultWordfilterProcess
 * @group filter
 */
class DefaultWordfilterProcessUnitTest extends UnitTestCase {

  /**
   * @var \Drupal\wordfilter\Plugin\WordfilterProcessInterface
   */
  protected $wordfilter_process = NULL;
  
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    
    $this->wordfilter_process = new DefaultWordfilterProcess([], 'default', []);
  }

  /**
   * @covers ::filterWords
   *
   * @dataProvider providerFilterWords
   *
   * @param $text
   *   An unfiltered string with words.
   * @param $expected
   *   The expected output string.
   */
  public function testfilterWords($text, $expected) {
    $config = new WordfilterConfiguration([
      'id' => 'my_superduper_wordfilter_configuration',
      'label' => 'My superduper Wordfilter configuration'],
      'wordfilter_configuration');
    $items = $config->getItems();
    $item = reset($items);
    $item->setFilterWords(['(Lorem)', 'Dolor', 'Amet']);
    $item->setSubstitute('***');

    $process = $this->wordfilter_process;
    $this->assertSame($expected, $process->filterWords($text, $config));
  }

  /**
   * Data provider for testfilterWords().
   *
   * TODO Add more reasonable data sets.
   *
   * @return array
   */
  public function providerFilterWords() {
    return [
      ['Lorem Ipsum Dolor Sit Amet', 'Lorem Ipsum *** Sit ***'],
      ['lorem ipsum dolor sit amet', 'lorem ipsum *** sit ***'],
      ['LoremIpsumDolorSitAmet', 'LoremIpsumDolorSitAmet'],
      ['(Lorem) *Ipsum* *Dolor* *Sit* *Amet*', '*** *Ipsum* ***** *Sit* *****'],
    ];
  }
}
