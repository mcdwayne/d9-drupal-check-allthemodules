<?php

namespace Drupal\Tests\tmgmt_smartling\Kernel;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartling\Plugin\tmgmt\Translator\SmartlingTranslator;
use Drupal\tmgmt_smartling\Plugin\tmgmt_file\Format\Xml;
use Smartling\File\Params\UploadFileParameters;

/**
 * Tests for smartling xml plugin.
 *
 * @group tmgmt_smartling
 */
class XmlPluginTest extends SmartlingTestBase {

  public function setUp() {
    parent::setUp();

    require_once __DIR__ . '/../../../vendor/autoload.php';
  }

  /**
   * Test filtering unrelated directives.
   */
  public function testEscapingUnEscapingOfPluralStringDelimiterSymbolOnExportImportSteps() {
    $source_string = "1 new comment@count new comments\x03@count brand new comments";

    \Drupal::state()->set('tmgmt.test_source_data', [
      'dummy' => [
        'deep_nesting' => [
          '#text' => $source_string,
          '#label' => 'Label of deep nested item @id',
        ],
        '#label' => 'Dummy item',
      ],
    ]);

    $job = parent::createJob();
    $job->addItem('test_source', 'test', 1);

    $job->settings = [];
    $job->translator = 'smartling';

    $xml_plugin = new Xml();
    $exported_content = $xml_plugin->export($job) . PHP_EOL;

    $this->assertTrue(strstr($exported_content, "1 new comment!PLURAL_STRING_DELIMITER@count new comments!PLURAL_STRING_DELIMITER@count brand new comments") !== FALSE);

    $file = file_save_data($exported_content, "public://test.xml", FILE_EXISTS_REPLACE);

    $imported_string = $xml_plugin->import($file->getFileUri())[1]['dummy']['deep_nesting']['#text'];

    $this->assertEquals($source_string, $imported_string);
  }

}
