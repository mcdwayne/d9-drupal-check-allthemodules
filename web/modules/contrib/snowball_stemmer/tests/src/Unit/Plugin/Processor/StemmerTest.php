<?php

namespace Drupal\Tests\snowball_stemmer\Unit\Plugin\Processor;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\snowball_stemmer\Plugin\search_api\processor\SnowballStemmer;
use Drupal\Tests\search_api\Unit\Processor\ProcessorTestTrait;
use Drupal\Tests\search_api\Unit\Processor\TestItemsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Stemmer" processor.
 *
 * @coversDefaultClass \Drupal\snowball_stemmer\Plugin\search_api\processor\SnowballStemmer
 *
 * @group snowball_stemmer
 */
class StemmerTest extends UnitTestCase {
  use ProcessorTestTrait;
  use TestItemsTrait;

  public static $modules = [
    'search_api',
    'snowball_stemmer',
  ];

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpMockContainer();

    $this->stemmerService = $this->getMockBuilder('Drupal\snowball_stemmer\Stemmer')
      ->disableOriginalConstructor()
      ->getMock();
    $this->container->set('snowball_stemmer.stemmer', $this->stemmerService);
    $this->languageManager = $this->getMock(LanguageManagerInterface::class);
    $this->container->set('language_manager', $this->languageManager);
    \Drupal::setContainer($this->container);

    $this->processor = new SnowballStemmer(array(), 'string', array());
  }

  /**
   * Tests language set of preprocessIndexItems() method.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessIndexItems() {
    $index = $this->getMock(IndexInterface::class);

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('en')
      ->willReturn(TRUE);

    $this->stemmerService
      ->expects($this->once())
      ->method('stem')
      ->with('backing')
      ->willReturn('back');

    $item_en = $this->getMockBuilder(ItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $item_en->method('getLanguage')->willReturn('en');
    $field_en = new Field($index, 'foo');
    $field_en->setType('text');
    $field_en->setValues(array(
      new TextValue('backing'),
    ));
    $item_en->method('getFields')->willReturn(array('foo' => $field_en));

    $this->processor->preprocessIndexItems([$item_en]);
  }

  /**
   * Tests string explode of preprocessIndexItems() method.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessWordsString() {
    $index = $this->getMock(IndexInterface::class);

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('nl')
      ->willReturn(TRUE);

    $this->stemmerService
      ->expects($this->exactly(3))
      ->method('stem')
      ->will($this->returnValueMap([
        ['twee', 'twee'],
        ['korte', 'kort'],
        ['zinnen', 'zin'],
      ]));

    $item = $this->getMockBuilder(ItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $item->method('getLanguage')->willReturn('nl');
    $field = new Field($index, 'foo');
    $field->setType('text');
    $field->setValues([
      new TextValue('Twee korte zinnen.'),
    ]);
    $item->method('getFields')->willReturn(['foo' => $field]);

    $this->processor->preprocessIndexItems([$item]);
  }

  /**
   * Tests string when language unknown.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessUnknownLanguage() {
    $index = $this->getMock(IndexInterface::class);

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('xx')
      ->willReturn(FALSE);

    $this->stemmerService
      ->expects($this->never())
      ->method('stem');

    $item = $this->getMockBuilder(ItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $item->method('getLanguage')->willReturn('xx');
    $field = new Field($index, 'foo');
    $field->setType('text');
    $field->setValues([
      new TextValue('Anything.'),
    ]);
    $item->method('getFields')->willReturn(['foo' => $field]);

    $this->processor->preprocessIndexItems([$item]);
  }

  /**
   * Tests string when more than one word sent.
   *
   * Occurs when string has not been tokenized, or if there is a quoted string.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessMultiWordString() {
    $index = $this->getMock(IndexInterface::class);

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('en')
      ->willReturn(TRUE);

    $item = $this->getMockBuilder(ItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $item->method('getLanguage')->willReturn('en');
    $field = new Field($index, 'foo');
    $field->setType('text');
    $field->setValues([
      new TextValue(" \tExtra  spaces \rappeared \n\tspaced-out  \r\n"),
    ]);
    $item->method('getFields')->willReturn(['foo' => $field]);

    $this->processor->preprocessIndexItems([$item]);
  }

  /**
   * Tests preprocessSearchQuery() method.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessSearchQuery() {
    $index = $this->getMock(IndexInterface::class);
    $index->expects($this->any())
      ->method('status')
      ->will($this->returnValue(TRUE));
    /** @var \Drupal\search_api\IndexInterface $index */
    $this->processor->setIndex($index);

    $language = $this->getMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('en'));
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('en')
      ->will($this->returnValue(TRUE));

    $this->stemmerService
      ->expects($this->exactly(4))
      ->method('stem')
      ->withConsecutive(
        ['fooing'],
        ['bar'],
        ['bary'],
        ['foo']
      )
      ->will($this->onConsecutiveCalls(
        'foo',
        'bar',
        'bar',
        'foo'
      ));

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($index);
    $keys = ['#conjunction' => 'AND', 'fooing', 'bar', 'bary foo'];
    $query->keys($keys);

    $this->processor->preprocessSearchQuery($query);
    $this->assertEquals(['#conjunction' => 'AND', 'foo', 'bar', 'bar foo'], $query->getKeys());
  }

  /**
   * Tests preprocessSearchQuery() method with no language.
   *
   * @covers ::preprocessIndexItems
   */
  public function testPreprocessSearchQueryNoLanguage() {
    $index = $this->getMock(IndexInterface::class);
    $index->expects($this->any())
      ->method('status')
      ->will($this->returnValue(TRUE));
    /** @var \Drupal\search_api\IndexInterface $index */
    $this->processor->setIndex($index);

    $language = $this->getMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('xxx'));
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    $this->stemmerService
      ->expects($this->once())
      ->method('setLanguage')
      ->with('xxx')
      ->will($this->returnValue(FALSE));

    $this->stemmerService
      ->expects($this->never())
      ->method('stem');

    $query = \Drupal::getContainer()
      ->get('search_api.query_helper')
      ->createQuery($index);
    $keys = ['#conjunction' => 'AND', 'foo', 'bar', 'bar foo'];
    $query->keys($keys);

    $this->processor->preprocessSearchQuery($query);
  }

  /**
   * Check exceptions/overrides configuration is set.
   */
  public function testOverrides() {
    $this->stemmerService
      ->expects($this->once())
      ->method('setOverrides')
      ->with(['word' => 'overridden']);

    $this->processor->setConfiguration(['exceptions' => ['word' => 'overridden']]);
    $this->processor->getStemmer();
  }

}
