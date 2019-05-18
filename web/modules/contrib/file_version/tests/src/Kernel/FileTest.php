<?php

namespace Drupal\Tests\file_version\Kernel;

/**
 * FileTest for cover Files with File Version.
 *
 * @group FileVersion
 */
class FileTest extends FileVersionTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'config', 'file', 'file_version'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->enableAllFiles();
  }

  /**
   * Don't add other fv parameter get.
   */
  public function testUniqueFileVersionParameter() {
    $uri = 'http://example.com/myfile.doc';
    $token = \Drupal::service('file_version')->getFileVersionToken($uri);
    $uri .= '?fv=' . $token;

    $url = file_create_url($uri);

    $query = parse_url($url, PHP_URL_QUERY);
    $fv_count = substr_count($query, 'fv=');

    $this->assertEquals(1, $fv_count, 'Only one File Version query parameter in the URL.');
  }

  /**
   * Cover absolute URLs. E.g.: http://example.com/myfile.doc.
   */
  public function testAbsoluteUrl() {
    $uri = 'http://example.com/myfile.doc';
    $url = file_create_url($uri);
    $this->assertTrue($this->isUrlAbsolute($url), 'Absolute URL keeps absolute.');
    $this->assertTrue($this->urlHasQueryParam($url), 'Absolute URL has File Version');
  }

  /**
   * Cover scheme URIs. E.g: public://myfile.doc.
   */
  public function testSchemeUri() {
    $scheme_uri = 'public://myfile.doc';
    $url = file_create_url($scheme_uri);
    $this->assertTrue($this->isUrlAbsolute($url), 'Scheme URI is converted to absolute URL.');
    $this->assertTrue($this->urlHasQueryParam($url), 'Scheme URI has File Version');
  }

  /**
   * Cover relative URLs. E.g.: modules/custom/mymodule/myfile.doc.
   */
  public function testRelativeUrl() {
    $relative_uri = 'modules/custom/mymodule/myfile.doc';
    $url = file_create_url($relative_uri);
    $this->assertTrue($this->isUrlAbsolute($url), 'Relative URL is converted to absolute URL.');
    $this->assertTrue($this->urlHasQueryParam($url), 'Relative URL has File Version');
  }

  /**
   * Cover root relative URLs. E.g.: /modules/custom/mymodule/myfile.doc.
   */
  public function testRootRelativeUrl() {
    $root_relative_uri = '/modules/custom/mymodule/myfile.doc';
    $url = file_create_url($root_relative_uri);
    $this->assertTrue(strpos($url, $root_relative_uri) === 0, 'Root relative URL keeps root relative.');
    $this->assertTrue($this->urlHasQueryParam($url), 'Root relative URL has File Version');
  }

  /**
   * Check if URLs works with custom File Version query parameter.
   */
  public function testCustomFileVersionParameter() {
    $custom_query_parameter = 'jv';
    $this->config('file_version.settings')->set('get_parameter_name', $custom_query_parameter)->save();
    $uri = 'http://example.com/myfile.doc';
    $url = file_create_url($uri);
    $this->assertTrue($this->urlHasQueryParam($url, $custom_query_parameter), 'URL works with custom query parameter.');
  }

  /**
   * Cover extensions blacklist.
   */
  public function testExtensionsBlacklist() {
    $doc_uri = 'http://example.com/myfile.doc';
    $pdf_uri = 'http://example.com/myfile.pdf';

    $this->config('file_version.settings')->set('extensions_blacklist', 'doc')->save();
    $doc_url = file_create_url($doc_uri);
    $pdf_url = file_create_url($pdf_uri);
    $this->assertFalse($this->urlHasQueryParam($doc_url), "Blacklisted extension doesn't have File Version: single value.");
    $this->assertTrue($this->urlHasQueryParam($pdf_url), 'Other extensions have File Version: single value.');

    $this->config('file_version.settings')->set('extensions_blacklist', 'doc, xml')->save();
    $doc_url = file_create_url($doc_uri);
    $pdf_url = file_create_url($pdf_uri);
    $this->assertFalse($this->urlHasQueryParam($doc_url), "Blacklisted extension doesn't have File Version: list.");
    $this->assertTrue($this->urlHasQueryParam($pdf_url), 'Other extensions have File Version: list.');
  }

}
