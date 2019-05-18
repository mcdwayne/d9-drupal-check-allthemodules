<?php

namespace Drupal\Tests\ossfs\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ossfs\OssfsDatabaseStorage;

/**
 * @group ossfs
 */
class OssfsDatabaseStorageTest extends KernelTestBase {

  use StorageTrait;

  /**
   * Modules to installs.
   *
   * @var array
   */
  protected static $modules = [
    'ossfs',
  ];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The ossfs database storage.
   *
   * @var \Drupal\ossfs\OssfsStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();
    $this->installSchema('ossfs', 'ossfs_file');
    $this->connection = $this->container->get('database');
    $this->storage = new OssfsDatabaseStorage($this->connection);
  }

  /**
   * Tests exists().
   */
  public function testExists() {
    $result = $this->storage->exists('oss://abc.jpg');
    $this->assertSame(FALSE, $result);

    $this->insertRecord('oss://abc.jpg');

    $result = $this->storage->exists('oss://abc.jpg');
    $this->assertSame(TRUE, $result);
  }

  /**
   * Tests read().
   */
  public function testRead() {
    $result = $this->storage->read('oss://abc.jpg');
    $this->assertSame(FALSE, $result);

    // Insert a record.
    $this->insertRecord('oss://abc.jpg');

    // Read storage.
    $result = $this->storage->read('oss://abc.jpg');
    $this->assertEquals([
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
      'filemime' => 'image/jpeg',
      'filesize' => 100,
      'imagesize' => '',
      'changed' => REQUEST_TIME,
    ], $result);
  }

  /**
   * Tests readMultiple().
   */
  public function testReadMultiple() {
    $result = $this->storage->readMultiple([
      'oss://abc.jpg',
      'oss://def.jpg',
      'oss://ghi.jpg',
    ]);
    $this->assertSame([], $result);

    // Insert records.
    $this->insertRecord('oss://abc.jpg');
    $this->insertRecord('oss://def.jpg');

    // Read storage.
    $result = $this->storage->readMultiple([
      'oss://abc.jpg',
      'oss://def.jpg',
      'oss://ghi.jpg',
    ]);
    $this->assertEquals([
      'oss://abc.jpg' => [
        'uri' => 'oss://abc.jpg',
        'type' => 'file',
        'filemime' => 'image/jpeg',
        'filesize' => 100,
        'imagesize' => '',
        'changed' => REQUEST_TIME,
      ],
      'oss://def.jpg' => [
        'uri' => 'oss://def.jpg',
        'type' => 'file',
        'filemime' => 'image/jpeg',
        'filesize' => 100,
        'imagesize' => '',
        'changed' => REQUEST_TIME,
      ],
    ], $result);
  }

  /**
   * Tests write().
   */
  public function testWrite() {
    // Ensure empty records.
    $result = $this->selectAllRecords();
    $this->assertEmpty($result);

    $data = [
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
      'filemime' => 'image/jpeg',
      'filesize' => 100,
      'imagesize' => '',
      'changed' => REQUEST_TIME,
    ];

    // Write storage.
    $result = $this->storage->write('oss://abc.jpg', $data);
    $this->assertSame(TRUE, $result);

    // Read record.
    $result = $this->selectAllRecords();
    $this->assertCount(1, $result);
    $this->assertEquals([
      'uri' => 'oss://abc.jpg',
      'type' => 'file',
      'filemime' => 'image/jpeg',
      'filesize' => 100,
      'imagesize' => '',
      'changed' => REQUEST_TIME,
    ], reset($result));

    // Write storage again with same data.
    $result = $this->storage->write('oss://abc.jpg', $data);
    $this->assertSame(TRUE, $result);
  }

  /**
   * Tests delete().
   */
  public function testDelete() {
    $result = $this->storage->delete('oss://abc.jpg');
    $this->assertSame(FALSE, $result);

    $this->insertRecord('oss://abc.jpg');

    $result = $this->storage->delete('oss://abc.jpg');
    $this->assertSame(TRUE, $result);
  }

  /**
   * Tests rename().
   */
  public function testRename() {
    // Rename a non-existent file.
    $result = $this->storage->rename('oss://abc.jpg', 'oss://def.jpg');
    $this->assertSame(FALSE, $result);

    $this->insertRecord('oss://abc.jpg');

    // Rename to a new file.
    $result = $this->storage->rename('oss://abc.jpg', 'oss://def.jpg');
    $this->assertSame(TRUE, $result);

    $this->insertRecord('oss://ghi.jpg');

    // Rename to an existent uri.
    $result = $this->storage->rename('oss://def.jpg', 'oss://ghi.jpg');
    $this->assertSame(TRUE, $result);
  }

  /**
   * Tests listAll().
   */
  public function testListAll() {
    try {
      $this->storage->listAll('abc');
      $this->fail('Empty path prefix must end with a slash');
    }
    catch (\InvalidArgumentException $e) {}

    $result = $this->storage->listAll('');
    $this->assertSame([], $result);
    $result = $this->storage->listAll('oss://0/');
    $this->assertSame([], $result);

    $this->insertRecord('oss://0', 'dir');
    $this->insertRecord('oss://0/a.jpg');
    $this->insertRecord('oss://0/1', 'dir');
    $this->insertRecord('oss://0/1/a.jpg');

    $result = $this->storage->listAll('');
    $this->assertEquals([
      'oss://0',
      'oss://0/1',
      'oss://0/1/a.jpg',
      'oss://0/a.jpg',
    ], $result);

    $result = $this->storage->listAll('oss://0/');
    $this->assertEquals([
      'oss://0/1',
      'oss://0/a.jpg',
    ], $result);

    $result = $this->storage->listAll('oss://1/');
    $this->assertSame([], $result);
  }

  /**
   * Tests the uri primary key.
   */
  public function testUriKey() {
    $this->insertRecord('oss://abc.jpg');
    $this->insertRecord('oss://Abc.jpg');
    $this->insertRecord('oss://a c.jpg');
    $this->insertRecord('oss://我们.jpg');

    $result = $this->selectAllRecords();
    $result = array_keys($result);
    sort($result);
    $this->assertEquals([
      'oss://Abc.jpg',
      'oss://a c.jpg',
      'oss://abc.jpg',
      'oss://我们.jpg',
    ], $result);
  }

}
