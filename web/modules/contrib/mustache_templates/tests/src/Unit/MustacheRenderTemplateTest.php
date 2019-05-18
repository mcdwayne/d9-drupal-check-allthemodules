<?php

namespace Drupal\mustache\Tests\Unit;

use Drupal\Core\Url;
use Drupal\mustache\Helpers\MustacheRenderTemplate;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MustacheRenderTemplate class.
 *
 * @coversDefaultClass \Drupal\mustache\Helpers\MustacheRenderTemplate
 * @group mustache
 */
class MustacheRenderTemplateTest extends UnitTestCase {

  /**
   * Test the static build method.
   *
   * @covers ::build
   */
  public function testBuild() {
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar'], $render_template->toRenderArray());
  }

  /**
   * Test the usingData method.
   *
   * @covers ::usingData
   */
  public function testUsingData() {
    $data = ['foo' => 'bar'];
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->usingData($data);
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar', '#data' => $data], $render_template->toRenderArray());
  }

  /**
   * Test the usingDataFromUrl method.
   *
   * @covers ::usingDataFromUrl
   */
  public function testUsingDataFromUrl() {
    $url_object = $url = Url::fromUri('https://drupal.org/');
    $url = $url_object;
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->usingDataFromUrl($url);
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar', '#data' => $url], $render_template->toRenderArray());

    $url = 'https://drupal.org/';
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->usingDataFromUrl($url);
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar', '#data' => $url_object], $render_template->toRenderArray());

    $url = '(&$%§shouldnotwork';
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $exception_thrown = FALSE;
    try {
      $render_template->usingDataFromUrl($url);
    }
    catch (\Exception $e) {
      $exception_thrown = TRUE;
    }
    $this->assertTrue($exception_thrown);
  }

  /**
   * Test the selectingSubsetFromData method.
   *
   * @covers ::selectingSubsetFromData
   */
  public function testSelectingSubsetFromData() {
    $select = ['foo', 'bar'];
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->selectingSubsetFromData($select);
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar', '#select' => $select], $render_template->toRenderArray());
  }

  /**
   * Test the withPlaceholder method.
   *
   * @covers ::withPlaceholder
   */
  public function testWithPlaceholder() {
    $placeholder = ['#markup' => 'foobar'];
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->withPlaceholder($placeholder);
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar', '#placeholder' => $placeholder], $render_template->toRenderArray());
  }

  /**
   * Test the withClientSynchronization method, including sub-methods.
   *
   * @covers ::withClientSynchronization
   */
  public function testWithClientSynchronization() {
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->withClientSynchronization();
    $this->assertArrayEquals(['#type' => 'mustache', '#template' => 'foo_bar', '#sync' => ['items' => [['period' => 0, 'delay' => 0]]]], $render_template->toRenderArray());

    $url_object = $url = Url::fromUri('https://drupal.org/');

    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_array_1 = $render_template->withClientSynchronization()
      ->usingDataFromUrl('https://drupal.org/')
      ->selectingSubsetFromData(['foo', 'bar'])
      ->periodicallyRefreshesAt(500)
      ->withWrapperTag('span')
      ->startsWhenElementWasTriggered('.button')
        ->atEvent('click')
        ->upToNTimes(2)
        ->toRenderArray();
    $expected = [
      '#type' => 'mustache',
      '#template' => 'foo_bar',
      '#sync' => [
        'items' => [[
          'data' => $url_object,
          'select' => ['foo', 'bar'],
          'period' => 500,
          'delay' => 0,
          'trigger' => [['.button', 'click', 2]],
        ]],
        'wrapper_tag' => 'span',
      ],
    ];
    $this->assertArrayEquals($expected, $render_template->toRenderArray());
    $this->assertArrayEquals($expected, $render_array_1);
  }

  /**
   * Tests for various expected render array results.
   */
  public function testRenderArrayResults() {
    $url_object = $url = Url::fromUri('https://drupal.org/');
    $render_template = MustacheRenderTemplate::build('foo_bar');
    $render_template->usingDataFromUrl($url_object);
    $render_template->selectingSubsetFromData(['foo', 'bar']);
    $expected = [
      '#type' => 'mustache',
      '#template' => 'foo_bar',
      '#data' => $url_object,
      '#select' => ['foo', 'bar'],
      '#sync' => ['items' => [['period' => 0, 'delay' => 100]]],
    ];
    $this->assertArrayEquals($expected, $render_template->withClientSynchronization()->startsDelayed(100)->toRenderArray());
  }

}
