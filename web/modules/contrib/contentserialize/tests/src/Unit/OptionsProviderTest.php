<?php

namespace Drupal\Tests\contentserialize\Unit;

use Drupal\contentserialize\Commands\ContentSerializeOptionsProvider;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a test for the content serialization options provider.
 *
 * @coversDefaultClass \Drupal\contentserialize\Commands\ContentSerializeOptionsProvider
 *
 * @group contentserialize
 */
class OptionsProviderTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $config;

  /**
   * A list of environment variables that have been set by the test.
   *
   * @var array
   */
  protected $variables = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Clean up any environment variables after tests.
    array_map('putenv', $this->variables);
    $this->variables = [];
    parent::tearDown();
  }

  /**
   * Test getting the format and context via an option.
   *
   * @covers ::getFormatAndContext
   */
  public function testGetFormatAndContextOption() {
    $this->config
      ->method('get')
      ->with($this->equalTo('drush.defaults.format'))
      ->willReturn('yaml');
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    $options = ['format' => 'yaml'];
    list($format, $context) = $provider->getFormatAndContext($options);

    $this->assertSame('yaml', $format);
    $this->assertSame([], $context);
  }

  /**
   * Test getting the format and context via configuration.
   *
   * @covers ::getFormatAndContext
   */
  public function testGetFormatAndContextConfiguration() {
    $this->config
      ->method('get')
      ->with($this->equalTo('drush.defaults.format'))
      ->willReturn('yaml');
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    list($format, $context) = $provider->getFormatAndContext([]);

    $this->assertSame('yaml', $format);
    $this->assertSame([], $context);
  }

  /**
   * Test that providing no format throws an exception.
   *
   * @covers ::getFormatAndContext
   */
  public function testGetFormatAndContextNoFormat() {
    $this->config
      ->method('get')
      ->with($this->equalTo('drush.defaults.format'))
      ->willReturn(NULL);
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());

    $this->setExpectedException(\RuntimeException::class);
    list($format, $context) = $provider->getFormatAndContext([]);
  }

  /**
   * Test getting the custom context for 'json'.
   *
   * @covers ::getFormatAndContext
   */
  public function testGetFormatAndContextJson() {
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    $options = ['format' => 'json'];
    list($format, $context) = $provider->getFormatAndContext($options);

    $this->assertSame('json', $format);
    $this->assertSame(['json_encode_options' => JSON_PRETTY_PRINT], $context);
  }

  /**
   * Test getting the custom context for 'hal_json'.
   *
   * @covers ::getFormatAndContext
   */
  public function testGetFormatAndContextHalJson() {
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    $options = ['format' => 'hal_json'];
    list($format, $context) = $provider->getFormatAndContext($options);

    $this->assertSame('hal_json', $format);
    $this->assertSame(['json_encode_options' => JSON_PRETTY_PRINT], $context);
  }

  /**
   * Test getting the export folder via an option.
   *
   * @covers ::getExportFolder
   */
  public function testGetExportFolderOptions() {
    $this->config
      ->method('get')
      ->with($this->equalTo('file.defaults.destination'))
      ->willReturn('test_destination_config');
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    // Ensure options override environment.
    $this->setEnv(ContentSerializeOptionsProvider::ENV_EXPORT_DESTINATION, 'test_destination_env');
    $options = ['destination' => 'test_destination_option'];

    $this->assertSame('test_destination_option', $provider->getExportFolder($options));
  }

  /**
   * Test getting the export folder via an environment variable.
   *
   * @covers ::getExportFolder
   */
  public function testGetExportFolderEnv() {
    $this->config
      ->method('get')
      ->with($this->equalTo('file.defaults.destination'))
      ->willReturn('test_destination_config');
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    $this->setEnv(ContentSerializeOptionsProvider::ENV_EXPORT_DESTINATION, 'test_destination_env');

    $this->assertSame('test_destination_env', $provider->getExportFolder([]));
  }

  /**
   * Test getting the export folder via configuration.
   *
   * @covers ::getExportFolder
   */
  public function testGetExportFolderConfiguration() {
    $this->config
      ->method('get')
      ->with($this->equalTo('file.defaults.export_destination'))
      ->willReturn('test_destination_config');
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());

    $this->assertSame('test_destination_config', $provider->getExportFolder([]));
  }

  /**
   * Test getting the excluded entity types/bundles via an option.
   *
   * @covers ::getExcluded
   */
  public function testGetExcludedOption() {
    $option = 'user,node:page';
    $this->config
      ->method('get')
      ->with($this->equalTo('drush.defaults.exclude'))
      ->willReturn($option);
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    // Ensure options override environment.
    $options = ['exclude' => $option];

    $expected = [
      'entity_type' => ['user'],
      'bundle' => ['node' => ['page']],
    ];
    $this->assertSame($expected, $provider->getExcluded($options));
  }

  /**
   * Test getting the excluded entity types/bundles via configuration.
   *
   * @covers ::getExcluded
   */
  public function testGetExcludedConfiguration() {
    $option = 'user,node:page';
    $this->config
      ->method('get')
      ->with($this->equalTo('drush.defaults.exclude'))
      ->willReturn($option);
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());

    $expected = [
      'entity_type' => ['user'],
      'bundle' => ['node' => ['page']],
    ];
    $this->assertSame($expected, $provider->getExcluded([]));
  }

  /**
   * Test getting the import folders via an option.
   *
   * @covers ::getImportFolders
   */
  public function testGetImportFolderOptions() {
    $this->config
      ->method('get')
      ->with($this->equalTo('file.defaults.import_sources'))
      ->willReturn(['test_source_config_1', 'test_source_config_2']);
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    // Ensure options override environment.
    $this->setEnv(ContentSerializeOptionsProvider::ENV_IMPORT_SOURCE, 'test_source_env_1,test_source_env_2');
    $options = ['source' => 'test_source_option_1,test_source_option_2'];

    $expected = ['test_source_option_1', 'test_source_option_2'];
    $this->assertSame($expected, $provider->getImportFolders($options));
  }

  /**
   * Test getting the import folders via an environment variable.
   *
   * @covers ::getImportFolders
   */
  public function testGetImportFolderEnv() {
    $this->config
      ->method('get')
      ->with($this->equalTo('file.defaults.import_sources'))
      ->willReturn(['test_source_config_1', 'test_source_config_2']);
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());
    $this->setEnv(ContentSerializeOptionsProvider::ENV_IMPORT_SOURCE, 'test_source_env_1,test_source_env_2');

    $expected = ['test_source_env_1', 'test_source_env_2'];
    $this->assertSame($expected, $provider->getImportFolders([]));
  }

  /**
   * Test getting the import folders via configuration.
   *
   * @covers ::getImportFolders
   */
  public function testGetImportFolderConfiguration() {
    $this->config
      ->method('get')
      ->with($this->equalTo('file.defaults.import_sources'))
      ->willReturn(['test_source_config_1', 'test_source_config_2']);
    $provider = new ContentSerializeOptionsProvider($this->config, $this->getStringTranslationStub());

    $expected = ['test_source_config_1', 'test_source_config_2'];
    $this->assertSame($expected, $provider->getImportFolders([]));
  }

  /**
   * Set an enviroment variable.
   *
   * All environment variables set in a test should use this method. On tear-
   * down the variables will be unset. It feels a bit dodgy doing this in a
   * test, maybe the env vars should come via a service that could be mocked.
   */
  protected function setEnv($name, $value) {
    putenv("$name=$value");
    $this->variables[] = $name;
  }

}
