<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\query\XmlTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\query {

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Site\Settings;
use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views_xml_backend\Plugin\views\query\Xml;
use Drupal\views_xml_backend\TestMessenger;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\query\Xml
 * @group views_xml_backend
 */
class XmlTest extends ViewsXmlBackendTestBase {

  /**
   * The XML query object.
   *
   * @var \Drupal\views_xml_backend\Plugin\views\query\Xml
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if (!defined('FILE_MODIFY_PERMISSIONS')) {
      define('FILE_MODIFY_PERMISSIONS', 2);
    }
    if (!defined('FILE_CREATE_DIRECTORY')) {
      define('FILE_CREATE_DIRECTORY', 1);
    }
    if (!defined('FILE_EXISTS_REPLACE')) {
      define('FILE_EXISTS_REPLACE', 1);
    }

    vfsStream::setup('vxb');

    new Settings(['views_xml_backend_cache_directory' => 'vfs://vxb/filecache']);

    // Create a mock and queue two responses.
    $mock = new MockHandler([
        new Response(200, ['X-Foo' => 'Bar']),
    ]);

    $handler = HandlerStack::create($mock);
    $this->client = new Client(['handler' => $handler]);

    $this->nullCache = new NullBackend('bin');

    $this->nullLogger = new NullLogger();

    $this->messenger = new TestMessenger();
  }

  public function testExecuteWithEmptyRowXpathDisplaysMessage() {
    $query = $this->getNewQueryObject([]);

    $view = $this->getMockedView();

    $query->init($view, $this->getMockedDisplay());
    $query->build($view);
    $query->execute($view);

    $this->assertSame(1, count($this->messenger->getMessages()));
    $this->assertSame(0, count($view->result));
  }

  public function testBasicXmlParsing() {
    $xml = <<<XML
<foo>
  <bar><value>1</value></bar>
  <bar><value>2</value></bar>
  <bar><value>3</value></bar>
  <bar><value>4</value></bar>
</foo>
XML;
    $mock = new MockHandler([
        new Response(200, [], $xml),
    ]);

    $handler = HandlerStack::create($mock);
    $this->client = new Client(['handler' => $handler]);

    $query = $this->getNewQueryObject([
      'xml_file' => 'http://example.com',
      'row_xpath' => '/foo/bar',
    ]);

    $view = $this->getMockedView();

    // Fake the fields.
    $view->field['field_1'] = (object) ['options' => ['xpath_selector' => 'value']];

    $query->build($view);
    $query->execute($view);

    // Check for no errors.
    $this->assertSame(0, count($this->messenger->getMessages()));

    $this->assertSame(4, count($view->result));

    foreach ($view->result as $index => $row) {
      $this->assertSame($index, $row->index);
      $value = (string) ($index + 1);
      $this->assertSame([0 => $value], $row->field_1);
    }
  }

  public function testAddExtraFieldsWorks() {
    $xml = <<<XML
<foo>
  <bar><value>1</value></bar>
  <bar><value>2</value></bar>
  <bar><value>3</value></bar>
  <bar><value>4</value></bar>
</foo>
XML;
    $mock = new MockHandler([
        new Response(200, [], $xml),
    ]);

    $handler = HandlerStack::create($mock);
    $this->client = new Client(['handler' => $handler]);

    $query = $this->getNewQueryObject([
      'xml_file' => 'http://example.com',
      'row_xpath' => '/foo/bar',
    ]);

    $view = $this->getMockedView();

    $query->addField('field_1', 'value');

    $query->build($view);
    $query->execute($view);

    // Check for no errors.
    $this->assertSame(0, count($this->messenger->getMessages()));

    $this->assertSame(4, count($view->result));

    foreach ($view->result as $index => $row) {
      $this->assertSame($index, $row->index);
      $value = (string) ($index + 1);
      $this->assertSame([0 => $value], $row->field_1);
    }
  }

  protected function getNewQueryObject(array $options) {
    $query = new Xml(
      [],
      '',
      [],
      $this->client,
      $this->nullCache,
      $this->nullLogger,
      $this->messenger
    );

    $query->setStringTranslation($this->getStringTranslationStub());
    $query->init($this->getMockedView(), $this->getMockedDisplay(), $options);

    return $query;
  }

}
}

namespace {
  if (!function_exists('file_prepare_directory')) {
    function file_prepare_directory(&$directory) {
      return mkdir($directory);
    }
  }

  if (!function_exists('file_unmanaged_save_data')) {
    function file_unmanaged_save_data($data, $destination) {
      file_put_contents($destination, $data);

      return $destination;
    }
  }
}
