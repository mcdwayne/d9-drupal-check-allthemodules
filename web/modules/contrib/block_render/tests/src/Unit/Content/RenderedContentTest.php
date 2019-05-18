<?php
/**
 * @file
 * Contains Drupal\Tests\block_render\Unit\Content\RenderedContentTest.
 */

namespace Drupal\Tests\block_render\Unit\Content;

use Drupal\block_render\Content\RenderedContent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the rendered content.
 *
 * @group block_render
 */
class RenderedContentTest extends UnitTestCase {

  /**
   * Tests the construct.
   */
  public function testRenderedContent() {
    $content = 'test content';

    new RenderedContent(['test' => $content], FALSE);
    new RenderedContent(['test' => $content], TRUE);
    new RenderedContent(['test' => $content, 'test2' => $content], TRUE);
  }

  /**
   * Tests setting a property on the class.
   */
  public function testSetFailure() {
    $this->setExpectedException('\LogicException', 'You cannot set properties.');

    $content = new RenderedContent();
    $content->content = 'some value';
  }

  /**
   * Tests adding content.
   */
  public function testAddContent() {
    $content = 'test content';

    $rendered = new RenderedContent();

    $rendered->addContent('test', $content);
    $rendered->addContent('test2', $content);

    $this->assertInternalType('array', $rendered->getContent());
    $this->assertArrayHasKey('test', $rendered->getContent());
    $this->assertEquals($content, $rendered->getContent()['test']);
    $this->assertArrayHasKey('test2', $rendered->getContent());
    $this->assertEquals($content, $rendered->getContent()['test2']);
  }

  /**
   * Tests getting the content.
   */
  public function testGetContent() {
    $content = 'test content';

    $rendered = new RenderedContent(['test' => $content], FALSE);

    $this->assertInternalType('array', $rendered->getContent());
    $this->assertArrayHasKey('test', $rendered->getContent());
    $this->assertEquals($content, $rendered->getContent()['test']);

    $rendered = new RenderedContent(['test' => $content], TRUE);

    $this->assertInternalType('array', $rendered->getContent());
    $this->assertArrayHasKey('test', $rendered->getContent());
    $this->assertEquals($content, $rendered->getContent()['test']);

    $rendered = new RenderedContent(['test' => $content, 'test2' => $content], TRUE);

    $this->assertInternalType('array', $rendered->getContent());
    $this->assertArrayHasKey('test', $rendered->getContent());
    $this->assertEquals($content, $rendered->getContent()['test']);
    $this->assertArrayHasKey('test2', $rendered->getContent());
    $this->assertEquals($content, $rendered->getContent()['test2']);
  }

  /**
   * Tests if the item is single.
   */
  public function testIsSingle() {
    $content = 'test content';

    $rendered = new RenderedContent(['test' => $content], FALSE);
    $this->assertFalse($rendered->isSingle());

    $rendered = new RenderedContent(['test' => $content], TRUE);
    $this->assertTrue($rendered->isSingle());

    $rendered = new RenderedContent(['test' => $content, 'test2' => $content], TRUE);
    $this->assertFalse($rendered->isSingle());
  }

  /**
   * Tests getting the iterator.
   */
  public function testGetIterator() {
    $rendered = new RenderedContent();
    $this->assertInstanceOf('\ArrayIterator', $rendered->getIterator());
  }

}
