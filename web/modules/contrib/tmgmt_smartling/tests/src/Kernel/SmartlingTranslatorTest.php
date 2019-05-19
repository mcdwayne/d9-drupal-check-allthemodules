<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator;
use Smartling\File\Params\UploadFileParameters;

/**
 * Tests for smartling translator.
 *
 * @group tmgmt_smartling
 */
class SmartlingTranslatorTest extends SmartlingTestBase {

  /**
   * @var SmartlingTranslatorBeingTested
   */
  protected $smartlingTranslator;

  /**
   * @var ModuleHandler
   */
  protected $moduleHandler;

  public function setUp() {
    parent::setUp();

    require_once __DIR__ . '/../../../vendor/autoload.php';

    $this->smartlingTranslator = $this->getMockBuilder(SmartlingTranslatorBeingTested::class)
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->moduleHandler = $this->getMockBuilder(ModuleHandler::class)
      ->setMethods(['alter'])
      ->disableOriginalConstructor()
      ->getMock();

    $this->smartlingTranslator->moduleHandler = $this->moduleHandler;
  }

  /**
   * Test filtering unrelated directives.
   */
  public function testFilterDirectivesFilterUnrelatedDirectives() {
    $actual = $this->smartlingTranslator->filterDirectives([
      'foo' => 'bar',
      'smartling.entity_escaping' => 'test',
      'smartling.variants_enabled' => 'test',
      'smartling.translate_paths' => 'test',
      'smartling.string_format_paths' => 'test',
      'smartling.placeholder_format_custom' => 'test',
      'smartling.placeholder_format' => 'test',
      'smartling.sltrans' => 'test',
      'smartling.source_key_paths' => 'test',
      'smartling.pseudo_inflation' => 'test',
      'smartling.instruction_paths' => 'test',
      'smartling.character_limit_paths' => 'test',
      'smartling.force_inline_for_tags' => 'test',
    ]);
    $expected = [
      'smartling.entity_escaping' => 'test',
      'smartling.variants_enabled' => 'test',
      'smartling.translate_paths' => 'test',
      'smartling.string_format_paths' => 'test',
      'smartling.placeholder_format_custom' => 'test',
      'smartling.placeholder_format' => 'test',
      'smartling.sltrans' => 'test',
      'smartling.source_key_paths' => 'test',
      'smartling.pseudo_inflation' => 'test',
      'smartling.instruction_paths' => 'test',
      'smartling.character_limit_paths' => 'test',
      'smartling.force_inline_for_tags' => 'test',
    ];

    $this->assertEquals($expected, $actual);
  }

  /**
   * Test filtering empty array: hook makes it empty.
   */
  public function testFilterDirectivesEmptyArrayHookMakesItEmpty() {
    $this->assertEquals($this->smartlingTranslator->filterDirectives([]), []);
  }

  /**
   * Test adding directives to upload params.
   */
  public function testAddSmartlingDirectives() {
    $this->moduleHandler->expects($this->once())
      ->method('alter');

    $job = $this->createJobWithItems([]);
    $actual = $this->smartlingTranslator
      ->addSmartlingDirectives(new UploadFileParameters(), $job)
      ->exportToArray();

    $this->assertEquals($actual['authorize'], FALSE);
    $this->assertTrue(preg_match('/^{"client":"smartling-api-sdk-php","version":"\d+\.\d+\.\d+"}$/', $actual['smartling.client_lib_id']));
    $this->assertEquals($actual['smartling.translate_paths'], 'html/body/div/div, html/body/div/span');
    $this->assertEquals($actual['smartling.string_format_paths'], 'html : html/body/div/div, @default : html/body/div/span');
    $this->assertEquals($actual['smartling.variants_enabled'], 'true');
    $this->assertEquals($actual['smartling.source_key_paths'], 'html/body/div/{div.sl-variant}, html/body/div/{span.sl-variant}');
    $this->assertEquals($actual['smartling.character_limit_paths'], 'html/body/div/limit');
    $this->assertEquals($actual['smartling.placeholder_format_custom'], '(@|%|!)[\w-]+');
  }
}

class SmartlingTranslatorBeingTested extends SmartlingTranslator {
  public $moduleHandler;

  public function filterDirectives(array $directives) {
    return parent::filterDirectives($directives);
  }

  public function addSmartlingDirectives(UploadFileParameters $params, JobInterface $job) {
    return parent::addSmartlingDirectives($params, $job);
  }
}
