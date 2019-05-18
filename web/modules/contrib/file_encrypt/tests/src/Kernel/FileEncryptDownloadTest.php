<?php

namespace Drupal\Tests\file_encrypt\Kernel;

use Drupal\file\Entity\File;
use Drupal\file_encrypt\EncryptStreamWrapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests downloading an encrypted file via HTTP.
 *
 * @group file_encrypt
 */
class FileEncryptDownloadTest extends FileEncryptTestBase {

  /**
   * The encrypted URI.
   *
   * @var string
   */
  protected $encryptedUri;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');

    $uri = EncryptStreamWrapper::SCHEME . '://encryption_profile_1/example.txt';
    file_put_contents($uri, 'test-data');
    $this->encryptedUri = $uri;
    File::create([
      'uri' => $this->encryptedUri,
    ])->save();
  }

  /**
   * Tests a file download via HTTP.
   */
  public function testFileDownloadViaHttp() {
    $request = Request::create('/encrypt/files/encryption_profile_1/example.txt');
    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel */
    $http_kernel = $this->container->get('http_kernel');

    $response = $http_kernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());


    ob_start();
    $response->send();
    $out = ob_get_contents();
    ob_end_clean();
    // @fixme This doesn't yet catch the actual response.
  }

}
