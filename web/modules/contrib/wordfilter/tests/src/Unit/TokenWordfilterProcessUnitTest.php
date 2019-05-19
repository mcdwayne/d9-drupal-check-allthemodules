<?php

namespace Drupal\Tests\wordfilter\Unit;

use \Drupal\Tests\UnitTestCase;
use \Drupal\wordfilter\Entity\WordfilterConfiguration;
use \Drupal\wordfilter\Plugin\WordfilterProcess\TokenWordfilterProcess;

/**
 * @coversDefaultClass \Drupal\wordfilter\Plugin\WordfilterProcess\TokenWordfilterProcess
 * @group filter
 */
class TokenWordfilterProcessUnitTest extends UnitTestCase {
  /**
   * @var \Drupal\wordfilter\Plugin\WordfilterProcessInterface
   */
  protected $wordfilter_process = NULL;
  
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->wordfilter_process = new TokenWordfilterProcess([], 'token', []);

    $token = $this->getMockBuilder('\Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()->getMock();

    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('get')
      ->with('token')
      ->will($this->returnValue($token));
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::filterWords
   *
   * @dataProvider providerFilterWordsWithToken
   *
   * @param $text
   *   An unfiltered string with words.
   * @param $expected
   *   The expected output string.
   */
  public function testfilterWordsWithToken($text, $expected) {
    $config = new WordfilterConfiguration([
      'id' => 'my_superduper_wordfilter_configuration',
      'label' => 'My superduper Wordfilter configuration'],
      'wordfilter_configuration');
    $items = $config->getItems();
    $item = reset($items);
    $item->setFilterWords(['(Lorem)', 'Dolor', 'Amet']);
    $item->setSubstitute('[any:token]');

    $process = $this->wordfilter_process;
    $this->assertSame($expected, $process->filterWords($text, $config));
  }

  /**
   * Data provider for testfilterWordsWithToken().
   *
   * TODO Add more reasonable data sets.
   *
   * @return array
   */
  public function providerFilterWordsWithToken() {
    return [
      ['Lorem Ipsum Dolor Sit Amet', 'Lorem Ipsum  Sit '],
      ['lorem ipsum dolor sit amet', 'lorem ipsum  sit '],
      ['LoremIpsumDolorSitAmet', 'LoremIpsumDolorSitAmet'],
      ['(Lorem) *Ipsum* *Dolor* *Sit* *Amet*', ' *Ipsum* ** *Sit* **'],
    ];
  }

  /**
   * @covers ::filterWords
   *
   * @dataProvider providerFilterWordsWithToken
   *
   * @param $text
   *   An unfiltered string with words.
   * @param $expected
   *   The expected output string.
   */
  public function testfilterWordsWithoutToken($text, $expected) {
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
   * Data provider for testfilterWordsWithoutToken().
   *
   * TODO Add more reasonable data sets.
   *
   * @return array
   */
  public function providerFilterWordsWithoutToken() {
    return [
      ['Lorem Ipsum Dolor Sit Amet', 'Lorem Ipsum *** Sit ***'],
      ['lorem ipsum dolor sit amet', 'lorem ipsum *** sit ***'],
      ['LoremIpsumDolorSitAmet', 'LoremIpsumDolorSitAmet'],
      ['(Lorem) *Ipsum* *Dolor* *Sit* *Amet*', '*** *Ipsum* ***** *Sit* *****'],
    ];
  }
}
