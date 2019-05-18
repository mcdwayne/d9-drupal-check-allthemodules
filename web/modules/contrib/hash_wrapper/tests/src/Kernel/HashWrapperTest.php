<?php

namespace Drupal\Tests\hash_wrapper\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\hash_wrapper\HashStream
 * @group hash_wrapper
 */
class HashWrapperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hash_wrapper', 'system'];

  /**
   * Tests hash wrapper.
   */
  public function testHashWrapper() {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme('hash');

    $this->assertEquals('Hashed public files', $wrapper->getName(), 'Stream wrapper name is correct.');
    $this->assertEquals('Public local files in hash dir served by the webserver.', $wrapper->getDescription(), 'Stream wrapper description is correct.');
    $this->assertEquals(StreamWrapperInterface::LOCAL_NORMAL, $wrapper->getType(), 'Wrapper type is correct.');

    $wrapper->setUri('hash://test.txt');
    $this->assertEquals('hash://test.txt', $wrapper->getUri(), 'Correct uri was returned.');

    $this->assertEquals('hash://', $wrapper->dirname('hash://test.txt'));
    $this->assertEquals('hash://level1', $wrapper->dirname('hash://level1/test.txt'));
    $this->assertEquals('hash://level1/level2', $wrapper->dirname('hash://level1/level2/test.txt'));

    $files_path = Settings::get('file_public_path', \Drupal::service('site.path') . '/files');
    // md5('test.txt') == 'dd18bf3a8e0a2a3e53e2661c7fb53534'
    $this->assertEquals('./dd/18/dd18bf3a8e0a2a3e53e2661c7fb53534.txt', $wrapper->uriTarget('hash://test.txt', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://dd/18'), 'Hash directory was created.');
    // md5('foo/test.txt') == '80923f25ee301e967ad4d55995594496'
    $this->assertEquals('foo/80/92/80923f25ee301e967ad4d55995594496.txt', $wrapper->uriTarget('hash://foo/test.txt', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://foo/80/92'), 'Hash directory was created.');
    // md5('foo/bar/test.txt') == '609fce6980e0ca4027987f8fd6e11d71'
    $this->assertEquals('foo/bar/60/9f/609fce6980e0ca4027987f8fd6e11d71.txt', $wrapper->uriTarget('hash://foo/bar/test.txt', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://foo/bar/60/9f'), 'Hash directory was created.');
    // md5('image.jpg') == '0d5b1c4c7f720f698946c7f6ab08f687'
    $this->assertEquals('./0d/5b/0d5b1c4c7f720f698946c7f6ab08f687.jpg', $wrapper->uriTarget('hash://image.jpg', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://0d/5b'), 'Hash directory was created.');
    // md5('foo/image.jpg') == '34bb2785b52e490b84560671b06d070b'
    $this->assertEquals('foo/34/bb/34bb2785b52e490b84560671b06d070b.jpg', $wrapper->uriTarget('hash://foo/image.jpg', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://foo/34/bb'), 'Hash directory was created.');
    // md5('foo/bar/image.jpg') == '15b6cef31b75ad3f5c48bfbb0e359990'
    $this->assertEquals('foo/bar/15/b6/15b6cef31b75ad3f5c48bfbb0e359990.jpg', $wrapper->uriTarget('hash://foo/bar/image.jpg', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://foo/bar/15/b6'), 'Hash directory was created.');
    // md5('image.jpg') == '0d5b1c4c7f720f698946c7f6ab08f687'
    $this->assertEquals('styles/some_style/hash/0d/5b/0d5b1c4c7f720f698946c7f6ab08f687.jpg', $wrapper->uriTarget('hash://styles/some_style/hash/image.jpg', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://styles/some_style/hash/0d/5b'), 'Hash directory was created.');
    // md5('foo/image.jpg') == '34bb2785b52e490b84560671b06d070b'
    $this->assertEquals('styles/some_style/hash/foo/34/bb/34bb2785b52e490b84560671b06d070b.jpg', $wrapper->uriTarget('hash://styles/some_style/hash/foo/image.jpg', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://styles/some_style/hash/foo/34/bb'), 'Hash directory was created.');
    // md5('foo/bar/image.jpg') == '15b6cef31b75ad3f5c48bfbb0e359990'
    $this->assertEquals('styles/some_style/hash/foo/bar/15/b6/15b6cef31b75ad3f5c48bfbb0e359990.jpg', $wrapper->uriTarget('hash://styles/some_style/hash/foo/bar/image.jpg', $files_path), 'Hashed URI is correct.');
    $this->assertTrue(is_dir('public://styles/some_style/hash/foo/bar/15/b6'), 'Hash directory was created.');
  }

}
