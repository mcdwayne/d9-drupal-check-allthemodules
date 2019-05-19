<?php

namespace Drupal\Tests\url_replace_filter\Unit;

use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Drupal\url_replace_filter\Plugin\Filter\UrlReplaceFilter;

// The plugin needs base_path(), but the normal test boot does not load it.
require_once 'core/includes/common.inc';

/**
 * @coversDefaultClass \Drupal\url_replace_filter\Plugin\Filter\UrlReplaceFilter
 * @group filter
 */
class UrlReplaceFilterTest extends UnitTestCase {

  /**
   * An instance of the filter plugin to test.
   *
   * @var \Drupal\url_replace_filter\Plugin\Filter\UrlReplaceFilter
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $GLOBALS['base_path'] = '/';
    $configuration['settings'] = [
      UrlReplaceFilter::SETTING_NAME => serialize([
        [
          'original' => '/blog/files/',
          'replacement' => '/sites/blog/files/',
        ],
      ]),
    ];
    $this->filter = new UrlReplaceFilter($configuration, UrlReplaceFilter::ID, ['provider' => 'test']);
    $this->filter->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::process
   *
   * @dataProvider providerProcess
   *
   * @param string $html
   *   Input HTML.
   * @param string $expected
   *   The expected output string.
   */
  public function testProcess(string $html, string $expected) {
    $this->assertSame($expected, $this->filter->process($html, Language::LANGCODE_SITE_DEFAULT)->__toString());
  }

  /**
   * Provides data for testProcess.
   *
   * @return array
   *   An array of test data.
   */
  public function providerProcess() {
    return [
      [
        '<a href="/blog" title="Blog">Blog</a>',
        '<a href="/blog" title="Blog">Blog</a>',
      ],
      [
        '<a href="/blog/files" title="Blog files">Blog</a>',
        '<a href="/blog/files" title="Blog files">Blog</a>',
      ],
      [
        '<a href="/blog/files/foo.png" title="Blog Foo">Blog</a>',
        '<a href="/sites/blog/files/foo.png" title="Blog Foo">Blog</a>',
      ],
      [
        '<img src="/blog" title="Blog">Blog</img>',
        '<img src="/blog" title="Blog">Blog</img>',
      ],
      [
        '<img src="/blog/files" title="Blog files">Blog</img>',
        '<img src="/blog/files" title="Blog files">Blog</img>',
      ],
      [
        '<img src="/blog/files/foo.png" alt="Blog Foo">Blog</img>',
        '<img src="/sites/blog/files/foo.png" alt="Blog Foo">Blog</img>',
      ],
      [
        '<img alt="Blog Foo" src="/blog/files/foo.png">Blog</img>',
        '<img alt="Blog Foo" src="/sites/blog/files/foo.png">Blog</img>',
      ],
    ];
  }

}
