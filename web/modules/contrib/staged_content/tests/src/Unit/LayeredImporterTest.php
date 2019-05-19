<?php

namespace Drupal\Tests\staged_content\Unit;

use Drupal\staged_content\DataProxy\JsonDataProxy;
use Drupal\staged_content\LayeredImporter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test the layered importer.
 *
 * @package staged_content
 */
class LayeredImporterTest extends UnitTestCase {

  /**
   * @covers Drupal\staged_content\LayeredImporter::keyDataByEntityType
   */
  public function testKeyDataByEntityType() {
    $layeredImporter = $this->generateBaseClass();

    $dataItems = [];
    $dataItems['node-uuid-1'] = new JsonDataProxy('dummy-filename-1.json', 'node-uuid-1', 'node', 'prod');
    $dataItems['node-uuid-2'] = new JsonDataProxy('dummy-filename-2.json', 'node-uuid-2', 'node', 'prod');
    $dataItems['term-uuid-1'] = new JsonDataProxy('dummy-filename-3.json', 'term-uuid-1', 'taxonomy_term', 'prod');
    $dataItems['file-uuid-1'] = new JsonDataProxy('dummy-filename-4.json', 'file-uuid-1', 'file', 'prod');
    $dataItems['file-uuid-2'] = new JsonDataProxy('dummy-filename-5.json', 'file-uuid-2', 'file', 'prod');

    $keyedData = $layeredImporter->keyDataByEntityType($dataItems);

    $this->assertArrayHasKey('node', $keyedData);
    $this->assertEquals(2, count($keyedData['node']));
    $this->assertArrayHasKey('node-uuid-1', $keyedData['node']);
    $this->assertArrayHasKey('node-uuid-2', $keyedData['node']);

    $this->assertArrayHasKey('taxonomy_term', $keyedData);
    $this->assertEquals(1, count($keyedData['taxonomy_term']));
    $this->assertArrayHasKey('term-uuid-1', $keyedData['taxonomy_term']);

    $this->assertArrayHasKey('file', $keyedData);
    $this->assertEquals(2, count($keyedData['file']));
    $this->assertArrayHasKey('file-uuid-1', $keyedData['file']);
    $this->assertArrayHasKey('file-uuid-2', $keyedData['file']);
  }

  /**
   * @covers Drupal\staged_content\LayeredImporter::prepareList
   */
  public function testPrepareListWithDuplicatePreservedId() {
    $itemList = $this->provideDataListWithDuplicatePreservedId();
    $layeredImporter = $this->generateBaseClass();

    $preparedList = $layeredImporter->prepareList('node', $itemList);

    // One item should have been preserved (with id 2)
    $this->assertArrayHasKey('preserved', $preparedList);
    $this->assertArrayHasKey(2, $preparedList['preserved']);

    // One item should have been shifted since item 2 has already been taken.
    $this->assertArrayHasKey('shifted', $preparedList);
    $this->assertEquals(1, count($preparedList['shifted']));

    // All items in the list should be data proxies.
    foreach ($preparedList['preserved'] as $item) {
      $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $item);
    }

    foreach ($preparedList['shifted'] as $item) {
      $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $item);
    }
  }

  /**
   * @covers Drupal\staged_content\LayeredImporter::prepareList
   */
  public function testPrepareListStandard() {
    $itemList = $this->provideDataListStandard();
    $layeredImporter = $this->generateBaseClass();

    $preparedList = $layeredImporter->prepareList('node', $itemList);

    // One item should have been preserved (with id 2)
    $this->assertArrayHasKey('new', $preparedList);
    $this->assertEquals(2, count($preparedList['new']));

    // All items in the list should be data proxies.
    foreach ($preparedList['new'] as $item) {
      $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $item);
    }
  }

  /**
   * Get a list with a duplicate preserved id.
   *
   * @return \Drupal\staged_content\DataProxy\DataProxyInterface[]
   *   List with the proxy items.
   */
  protected function provideDataListWithDuplicatePreservedId() {
    $itemList = [];
    $itemList['uuid-node-1'] = $this->generateDataProxyMock([
      'meta' => [
        'preserve_original_id' => TRUE,
        'original_id' => 2,
      ],
    ]);
    $itemList['uuid-node-2'] = $this->generateDataProxyMock([
      'meta' => [
        'preserve_original_id' => TRUE,
        'original_id' => 2,
      ],
    ]);
    return $itemList;
  }

  /**
   * Get a list with a duplicate preserved id.
   *
   * @return \Drupal\staged_content\DataProxy\DataProxyInterface[]
   *   List with the proxy items.
   */
  protected function provideDataListStandard() {
    $itemList = [];
    $itemList['uuid-node-1'] = $this->generateDataProxyMock([
      'meta' => [
        'preserve_original_id' => FALSE,
      ],
    ]);
    $itemList['uuid-node-2'] = $this->generateDataProxyMock([
      'meta' => [
        'preserve_original_id' => FALSE,
      ],
    ]);
    return $itemList;
  }

  /**
   * Generate a basic mocked item.
   *
   * @return \Drupal\staged_content\LayeredImporter
   *   Layered import with mocked services.
   */
  protected function generateBaseClass() {
    $serializer = $this->createMock('\Symfony\Component\Serializer\Serializer');
    $entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManager');
    $accountSwitcher = $this->createMock('\Drupal\Core\Session\AccountSwitcher');

    $layeredImporter = new LayeredImporter($serializer, $entityTypeManager, $accountSwitcher);

    // Buffer the output to prevent printing it out in between the test results.
    $layeredImporter->setOutput(new BufferedOutput());
    return $layeredImporter;
  }

  /**
   * Get a mock data proxy where you can set the data in the file.
   *
   * @param array $mockData
   *   Data to replace the actual file data.
   * @param string|null $uuid
   *   String uuid or null (for a standard sample)
   *
   * @return \Drupal\staged_content\DataProxy\DataProxyInterface
   *   Mocked proxy interface with the data.
   */
  protected function generateDataProxyMock(array $mockData, string $uuid = NULL) {
    $dataProxy = $this->createMock('Drupal\staged_content\DataProxy\JsonDataProxy');
    $dataProxy->method('getData')->willReturn($mockData);
    $uuid = isset($uuid) ? $uuid : 'sample-uuid';
    $dataProxy->method('getUuid')->willReturn($uuid);
    return $dataProxy;
  }

}
