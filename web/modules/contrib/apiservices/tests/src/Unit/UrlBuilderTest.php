<?php

/**
 * @file
 * Contains \Drupal\Tests\apiservices\Unit\UrlBuilderTest.
 */

namespace Drupal\Tests\apiservices\Unit;

use Drupal\apiservices\UrlBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\apiservices\UrlBuilder
 * @group apiservices
 */
class UrlBuilderTest extends UnitTestCase {

  /**
   * @var \Drupal\apiservices\UrlBuilder
   */
  protected $urlBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->urlBuilder = new UrlBuilder('https:', '//www.example.com');
  }

  /**
   * Tests getting a placeholder that does not exist.
   *
   * @expectedException \OutOfBoundsException
   */
  public function testInvalidPlaceholder() {
    $this->urlBuilder->getPlaceholder('test');
  }

  /**
   * Tests getting a query parameter that does not exist.
   *
   * @expectedException \OutOfBoundsException
   */
  public function testInvalidQueryParameter() {
    $this->urlBuilder->getQueryParameter('test');
  }

  /**
   * Tests validation of URL schemes.
   *
   * @dataProvider getInvalidSchemes
   * @expectedException \Exception
   */
  public function testInvalidScheme($scheme) {
    $this->urlBuilder->setScheme($scheme);
  }

  /**
   * Gets a list of invalid URL schemes.
   */
  public function getInvalidSchemes() {
    return [
      [''],
      [':scheme'],
      ['sch:eme'],
    ];
  }

  /**
   * Tests getting the path portion of a URL.
   */
  public function testPath() {
    $this->assertEquals($this->urlBuilder->getPath(), '');
  }

  /**
   * Tests various placeholder methods.
   */
  public function testPlaceholders() {
    $this->urlBuilder->setPlaceholder('a', 'b');
    $this->assertEquals($this->urlBuilder->getPlaceholder('a'), 'b');
    $this->assertEquals($this->urlBuilder->getPlaceholders(), ['a' => 'b']);
    $this->urlBuilder->clearPlaceholders();
    $this->assertFalse($this->urlBuilder->hasPlaceholder('a'));
  }

  /**
   * Tests various query parameter methods.
   */
  public function testQueryParameter() {
    $this->urlBuilder->setQueryParameter('sort', 'asc');
    $this->urlBuilder->setQueryParameter('page', '1');
    $this->assertEquals($this->urlBuilder->getQueryParameter('sort'), 'asc');
    $this->assertEquals($this->urlBuilder->getQueryParameter('page'), '1');
    $this->assertEquals($this->urlBuilder->getQuery(), 'sort=asc&page=1');
    // Setting to an empty value is the same as removing the parameter.
    $this->urlBuilder->setQueryParameter('sort', '');
    $this->assertFalse($this->urlBuilder->hasQueryParameter('sort'));
    // Clear the remaining parameters.
    $this->urlBuilder->clearQuery();
    $this->assertFalse($this->urlBuilder->hasQueryParameter('page'));
  }

  /**
   * Tests changing the URL scheme.
   */
  public function testScheme() {
    $this->urlBuilder->setScheme('http:');
    $this->assertEquals($this->urlBuilder->getUrl(), 'http://www.example.com');
    $this->urlBuilder->setScheme('https');
    $this->assertEquals($this->urlBuilder->getUrl(), 'https://www.example.com');
  }

}
