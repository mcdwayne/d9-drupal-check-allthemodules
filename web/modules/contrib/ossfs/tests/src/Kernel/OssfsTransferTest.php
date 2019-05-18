<?php

namespace Drupal\Tests\ossfs\Kernel;

use OSS\Core\OssException;

/**
 * @group ossfs
 */
class OssfsTransferTest extends OssfsRemoteTestBase {

  /**
   * The ossfs transfer.
   *
   * @var \Drupal\ossfs\OssfsTransfer
   */
  protected $transfer;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();
    $this->transfer = $this->container->get('ossfs_transfer');
  }

  /**
   * Tests uploadPublic().
   */
  public function testUploadPublic() {
    $uri1 = 'oss://a.txt';
    $uri2 = 'oss://0/a.txt';
    $this->cleanup = [
      $uri1,
      $uri2,
    ];

    file_put_contents('public://a.txt', 'a');
    mkdir('public://0');
    file_put_contents('public://0/a.txt', '0a');

    // Upload public files.
    iterator_to_array($this->transfer->uploadPublic('', TRUE));

    $expect = [
      'uri' => $uri1,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 1,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the $uri1 file exists in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri1));
    $metadata = $this->normalizeResponse($uri1, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the $uri1 file exists in local storage.
    $result = $this->storage->read($uri1);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    $expect = [
      'uri' => $uri2,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 2,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the $uri2 file exists in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri2));
    $metadata = $this->normalizeResponse($uri2, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the $uri2 file exists in local storage.
    $result = $this->storage->read($uri2);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    $uri_dir = 'oss://0';
    $expect = [
      'uri' => $uri_dir,
      'type' => 'dir',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the directory does not exist in OSS, the pseudo directory is only
    // created in local storage.
    try {
      $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri_dir) . '/');
      $this->fail('File not found in OSS');
    }
    catch (OssException $e) {}

    // Ensure the $uri_dir directory exists in local storage.
    $result = $this->storage->read($uri_dir);
    $this->assertEquals($expect, $this->normalizeStorage($result));
  }

  /**
   * Tests syncMetadata().
   */
  public function testSyncMetadata() {
    $uri1 = 'oss://a.txt';
    $uri2 = 'oss://0/a.txt';
    $uri3 = 'oss://0/1/a.txt';
    $uri_dir = 'oss://0/2';
    $this->cleanup = [
      $uri1,
      $uri2,
      $uri3,
      $uri_dir . '/',
    ];

    // Write a old record.
    $old_uri = 'oss://old.txt';
    $this->storage->write($old_uri, [
      'uri' => $uri1,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 100,
      'imagesize' => '',
      'changed' => time(),
    ]);
    $this->assertTrue($this->storage->exists($old_uri));

    // Upload 3 files.
    $this->client->putObject($this->ossfsConfig['bucket'], $this->getKey($uri1), 'a');
    $this->client->putObject($this->ossfsConfig['bucket'], $this->getKey($uri2), '0a');
    $this->client->putObject($this->ossfsConfig['bucket'], $this->getKey($uri3), '01a');
    $this->client->createObjectDir($this->ossfsConfig['bucket'], $this->getKey($uri_dir));

    // Sync metadata from OSS.
    $this->transfer->syncMetadata();

    // Ensure the $uri1 file was synced to local storage.
    $expect = [
      'uri' => $uri1,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 1,
      'imagesize' => '',
      // 'changed' => time(),
    ];
    $result = $this->storage->read($uri1);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the $uri2 file was synced to local storage.
    $expect = [
      'uri' => $uri2,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 2,
      'imagesize' => '',
      // 'changed' => time(),
    ];
    $result = $this->storage->read($uri2);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the $uri3 file was synced to local storage.
    $expect = [
      'uri' => $uri3,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 3,
      'imagesize' => '',
      // 'changed' => time(),
    ];
    $result = $this->storage->read($uri3);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the $uri_dir directory was not synced to local storage.
    $this->assertFalse($this->storage->exists($uri_dir));

    // Ensure the new 'oss://0' directory was created in local storage.
    $uri_new_dir = 'oss://0';
    $expect = [
      'uri' => $uri_new_dir,
      'type' => 'dir',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      // 'changed' => time(),
    ];
    $result = $this->storage->read($uri_new_dir);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the new 'oss://0/1' directory was created in local storage.
    $uri_new_dir = 'oss://0/1';
    $expect = [
      'uri' => $uri_new_dir,
      'type' => 'dir',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      // 'changed' => time(),
    ];
    $result = $this->storage->read($uri_new_dir);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure total 5 records were synced.
    $this->assertEquals(5, count($this->storage->listAll('')));

    // Ensure the old record is gone.
    $this->assertFalse($this->storage->exists($old_uri));
  }

}
