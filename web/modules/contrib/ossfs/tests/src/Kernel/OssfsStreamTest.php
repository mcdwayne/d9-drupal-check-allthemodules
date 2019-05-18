<?php

namespace Drupal\Tests\ossfs\Kernel;

use Drupal\ossfs\StreamWrapper\OssfsStream;
use OSS\Core\OssException;

/**
 * @group ossfs
 */
class OssfsStreamTest extends OssfsRemoteTestBase {

  /**
   * Tests file IO.
   */
  public function testFileIO() {
    $uri = 'oss://abc.txt';
    $uri2 = 'oss://def.txt';
    $new_uri = 'oss://new-abc.txt';
    $this->cleanup = [$uri, $uri2, $new_uri];

    /********************************** WRITE *********************************/
    // Write a file through stream wrapper.
    $handle = fopen($uri, 'wb');
    $this->assertEquals(0, ftell($handle));

    // Write 7 bytes.
    $this->assertEquals(7, fwrite($handle, 'abc.txt'));
    $this->assertTrue(fflush($handle));
    $this->assertTrue(fclose($handle));

    $expect = [
      'uri' => $uri,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 7,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the file exists in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri));
    $metadata = $this->normalizeResponse($uri, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the file exists in local storage.
    $result = $this->storage->read($uri);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the file exists through stream wrapper.
    $this->assertTrue(file_exists($uri));
    $this->assertTrue(is_file($uri));

    /********************************** READ **********************************/
    // Read a file through stream wrapper.
    $handle = fopen($uri, 'rb');
    $this->assertEquals(0, ftell($handle));

    // Read 3 bytes.
    $this->assertEquals('abc', fread($handle, 3));
    $this->assertEquals(3, ftell($handle));
    $this->assertFalse(feof($handle));

    // Read remaining bytes to end.
    $this->assertEquals('.txt', fread($handle, 10));
    $this->assertEquals(7, ftell($handle));
    $this->assertTrue(feof($handle));

    // Seekable by default.
    $this->assertSame(TRUE, stream_get_meta_data($handle)['seekable']);

    // Rewind.
    rewind($handle);
    $this->assertEquals(0, ftell($handle));
    $this->assertEquals('abc.txt', fread($handle, 10));
    // php://temp needs to read twice to get the eof
    // $this->assertTrue(feof($handle));

    // Seek to position 3.
    fseek($handle, 3);
    $this->assertEquals(3, ftell($handle));
    $this->assertEquals('.txt', fread($handle, 10));

    // Stat.
    $result = stat($uri);
    $this->assertEquals(7, $result['size']);
    $this->assertEquals(0100777, $result['mode']);
    $this->assertEquals(7, filesize($uri));

    $this->assertTrue(fclose($handle));

    // Open non-existent file.
    try {
      fopen('oss://non-existent.txt', 'rb');
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    /*************************** APPEND TO EXISTENT ***************************/
    // Append a file through stream wrapper.
    $handle = fopen($uri, 'ab');
    $this->assertEquals(7, ftell($handle));

    // Append 3 bytes.
    $this->assertEquals(3, fwrite($handle, '123'));
    $this->assertTrue(fflush($handle));
    $this->assertTrue(fclose($handle));

    $expect = [
      'uri' => $uri,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 10,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the file changed in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri));
    $metadata = $this->normalizeResponse($uri, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the file changed in local storage.
    $result = $this->storage->read($uri);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the file stat changed.
    $result = stat($uri);
    $this->assertEquals(10, $result['size']);
    $this->assertEquals(0100777, $result['mode']);
    $this->assertEquals(10, filesize($uri));


    /************************** APPEND TO NON-EXISTENT ************************/
    $handle = fopen($uri2, 'ab');
    $this->assertEquals(0, ftell($handle));

    // Append 3 bytes.
    $this->assertEquals(3, fwrite($handle, '123'));
    $this->assertTrue(fflush($handle));
    $this->assertTrue(fclose($handle));

    $expect = [
      'uri' => $uri2,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 3,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the file exists in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri2));
    $metadata = $this->normalizeResponse($uri2, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the file exists in local storage.
    $result = $this->storage->read($uri2);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the file exists through stream wrapper.
    $this->assertTrue(file_exists($uri2));
    $this->assertTrue(is_file($uri2));

    // Ensure the file stat exists.
    $result = stat($uri2);
    $this->assertEquals(3, $result['size']);
    $this->assertEquals(0100777, $result['mode']);
    $this->assertEquals(3, filesize($uri2));

    /************************** RENAME TO EXISTENT ****************************/
    // Rename to an existent file.
    $this->assertTrue(rename($uri, $uri2));

    $expect = [
      'uri' => $uri2,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 10,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the $uri2 changed in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri2));
    $metadata = $this->normalizeResponse($uri2, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the $uri2 changed in local storage.
    $result = $this->storage->read($uri2);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the $uri2 exists through stream wrapper.
    $this->assertTrue(file_exists($uri2));
    $this->assertTrue(is_file($uri2));

    // Ensure the $uri2 stat changed.
    $result = stat($uri2);
    $this->assertEquals(10, $result['size']);
    $this->assertEquals(0100777, $result['mode']);
    $this->assertEquals(10, filesize($uri2));

    // Ensure the $uri does not exist in OSS.
    try {
      $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri));
      $this->fail('File not found in OSS');
    }
    catch (OssException $e) {}

    // Ensure the $uri does not exist in local storage.
    $this->assertFalse($this->storage->exists($uri));

    // Ensure the $uri does not exist through stream wrapper.
    $this->assertFalse(file_exists($uri));
    $this->assertFalse(is_file($uri));

    // Ensure the $uri stat does not exist.
    try {
      stat($uri);
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    // Rename a non-existent file.
    try {
      rename('oss://non-existent.txt', $uri2);
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    /************************ RENAME TO NON-EXISTENT **************************/
    // Rename to a new file.
    $this->assertTrue(rename($uri2, $new_uri));

    $expect = [
      'uri' => $new_uri,
      'type' => 'file',
      'filemime' => 'text/plain',
      'filesize' => 10,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the $new_uri exists in OSS.
    $response = $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($new_uri));
    $metadata = $this->normalizeResponse($new_uri, $response);
    $this->assertEquals($expect, $metadata);

    // Ensure the $new_uri exists in local storage.
    $result = $this->storage->read($new_uri);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the $new_uri exists through stream wrapper.
    $this->assertTrue(file_exists($new_uri));
    $this->assertTrue(is_file($new_uri));

    // Ensure the $new_uri stat exists.
    $result = stat($new_uri);
    $this->assertEquals(10, $result['size']);
    $this->assertEquals(0100777, $result['mode']);
    $this->assertEquals(10, filesize($new_uri));

    // Ensure the $uri2 does not exist in OSS.
    try {
      $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri2));
      $this->fail('File not found in OSS');
    }
    catch (OssException $e) {}

    // Ensure the $uri2 does not exist in local storage.
    $this->assertFalse($this->storage->exists($uri2));

    // Ensure the $uri2 does not exist through stream wrapper.
    $this->assertFalse(file_exists($uri2));
    $this->assertFalse(is_file($uri2));

    // Ensure the $uri2 stat does not exist.
    try {
      stat($uri2);
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    /******************************** UNLINK **********************************/
    $this->assertTrue(unlink($new_uri));

    // Ensure the $new_uri does not exist in OSS.
    try {
      $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($new_uri));
      $this->fail('File not found in OSS');
    }
    catch (OssException $e) {}

    // Ensure the $new_uri does not exist in local storage.
    $this->assertFalse($this->storage->exists($new_uri));

    // Ensure the $new_uri does not exist through stream wrapper.
    $this->assertFalse(file_exists($new_uri));
    $this->assertFalse(is_file($new_uri));

    // Ensure the $new_uri stat does not exist.
    try {
      stat($new_uri);
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    // Unlink a non-existent file.
    try {
      unlink($new_uri);
      $this->fail('File not found');
    }
    catch (\Exception $e) {}
  }

  /**
   * Tests imagesize.
   */
  public function testImagesize() {
    $uri_image = 'oss://abc.jpg';
    $uri_image_no_extension = 'oss://abc';
    $this->cleanup = [$uri_image, $uri_image_no_extension];

    $source = 'https://ss3.bdstatic.com/70cFv8Sh_Q1YnxGkpoWK1HF6hhy/it/u=445661229,1591172070&fm=11&gp=0.jpg';

    // Copy file with extension to OSS.
    copy($source, $uri_image);

    // Ensure imagesize of the file from OSS is correct.
    $result = getimagesize(file_create_url($uri_image));
    $this->assertEquals([
      533,
      299,
      IMAGETYPE_JPEG,
      'width="533" height="299"',
      'bits' => 8,
      'channels' => 3,
      'mime' => 'image/jpeg',
    ], $result);

    // Ensure imagesize of the file in local storage is correct.
    $result = $this->storage->read($uri_image);
    $this->assertEquals([
      'uri' => $uri_image,
      'type' => 'file',
      'filemime' => 'image/jpeg',
      'filesize' => 18072,
      'imagesize' => '533,299,' . IMAGETYPE_JPEG,
      // 'changed' => time(),
    ], $this->normalizeStorage($result));

    // Copy file without extension to OSS.
    copy($source, $uri_image_no_extension);

    // Ensure imagesize of the file in OSS is correct.
    $result = getimagesize(file_create_url($uri_image_no_extension));
    $this->assertEquals([
      533,
      299,
      IMAGETYPE_JPEG,
      'width="533" height="299"',
      'bits' => 8,
      'channels' => 3,
      'mime' => 'image/jpeg',
    ], $result);


    // Ensure imagesize of file in local storage is correct.
    $result = $this->storage->read($uri_image_no_extension);
    $this->assertEquals([
      'uri' => $uri_image_no_extension,
      'type' => 'file',
      'filemime' => 'image/jpeg',
      'filesize' => 18072,
      'imagesize' => '533,299,' . IMAGETYPE_JPEG,
      // 'changed' => time(),
    ], $this->normalizeStorage($result));
  }

  /**
   * Tests mkdir().
   */
  public function testMkdir() {
    $uri = 'oss://0';
    mkdir($uri);

    $expect = [
      'uri' => $uri,
      'type' => 'dir',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the directory does not exist in OSS, the pseudo directory is only
    // created in local storage.
    try {
      $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($uri) . '/');
      $this->fail('File not found in OSS');
    }
    catch (OssException $e) {}

    // Ensure the directory exists in local storage.
    $result = $this->storage->read($uri);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the directory exists through stream wrapper.
    $this->assertTrue(file_exists($uri));
    $this->assertTrue(is_dir($uri));

    // Ensure the directory stat exists.
    $result = stat($uri);
    $this->assertEquals(0, $result['size']);
    $this->assertEquals(0040777, $result['mode']);
    $this->assertEquals(0, filesize($uri));

    try {
      $this->assertFalse(fopen($uri, 'rb'));
      $this->fail('Cannot open file for a directory');
    }
    catch (\Exception $e) {}

    try {
      mkdir('oss://0');
      $this->fail('File already exists');
    }
    catch (\Exception $e) {}

    // Mkdir with a trailing slash.
    $uri = 'oss://1/';
    $clean_uri = 'oss://1';
    mkdir($uri);

    $expect = [
      'uri' => $clean_uri,
      'type' => 'dir',
      'filemime' => '',
      'filesize' => 0,
      'imagesize' => '',
      // 'changed' => time(),
    ];

    // Ensure the directory does not exist in OSS, the pseudo directory is only
    // created in local storage.
    try {
      $this->client->getObjectMeta($this->ossfsConfig['bucket'], $this->getKey($clean_uri) . '/');
      $this->fail('File not found in OSS');
    }
    catch (OssException $e) {}

    // Ensure the directory exists in local storage.
    $result = $this->storage->read($clean_uri);
    $this->assertEquals($expect, $this->normalizeStorage($result));

    // Ensure the directory exists through stream wrapper.
    $this->assertTrue(file_exists($clean_uri));
    $this->assertTrue(is_dir($clean_uri));
    $this->assertTrue(file_exists($uri));
    $this->assertTrue(is_dir($uri));

    // Create directories recursively.
    $this->assertTrue(mkdir('oss://2/3/4', 0777, TRUE));
    $this->assertTrue($this->storage->exists('oss://2'));
    $this->assertTrue($this->storage->exists('oss://2/3'));
    $this->assertTrue($this->storage->exists('oss://2/3/4'));
    $this->assertTrue(file_exists('oss://2'));
    $this->assertTrue(file_exists('oss://2/3'));
    $this->assertTrue(file_exists('oss://2/3/4'));

    // Ensure the 'oss://' root exists by default.
    $result = stat('oss://');
    $this->assertEquals(0, $result['size']);
    $this->assertEquals(0040777, $result['mode']);
    $this->assertEquals(0, filesize('oss://'));

    // Create the 'oss://' root directory.
    $this->assertTrue(mkdir('oss://'));
    $this->assertTrue(mkdir('oss:///'));
  }

  /**
   * Tests readdir().
   */
  public function testReadDir() {
    $this->cleanup = [
      'oss://0/a.txt',
      'oss://0/b.txt',
      'oss://0/1/a.txt',
      'oss://0/1/b.txt',
    ];

    // Create directory 'oss://0' and puts 2 files.
    $this->assertTrue(mkdir('oss://0'));
    $this->assertEquals(2, file_put_contents('oss://0/a.txt', '0a'));
    $this->assertEquals(2, file_put_contents('oss://0/b.txt', '0b'));
    // Create directory 'oss://0/1' and puts 2 files.
    $this->assertTrue(mkdir('oss://0/1'));
    $this->assertEquals(3, file_put_contents('oss://0/1/a.txt', '01a'));
    $this->assertEquals(3, file_put_contents('oss://0/1/b.txt', '01b'));

    // Read directory 'oss://0'.
    $handle = opendir('oss://0');
    $this->assertEquals('1', readdir($handle));
    $this->assertEquals('a.txt', readdir($handle));
    $this->assertEquals('b.txt', readdir($handle));
    $this->assertFalse(FALSE, readdir($handle));

    // Rewind directory 'oss://0'.
    rewinddir($handle);
    $this->assertEquals('1', readdir($handle));
    $this->assertEquals('a.txt', readdir($handle));
    $this->assertEquals('b.txt', readdir($handle));
    $this->assertFalse(FALSE, readdir($handle));

    closedir($handle);

    // Scan dir.
    $this->assertEquals([
      '1',
      'a.txt',
      'b.txt'
    ], scandir('oss://0'));
    // Scan dir.
    $this->assertEquals([
      '0',
    ], scandir('oss://'));
  }

  /**
   * Tests rmdir().
   */
  public function testRmDir() {
    $this->cleanup = [
      'oss://0/a.txt',
    ];

    // Create directories and puts files.
    $this->assertTrue(mkdir('oss://0'));
    $this->assertEquals(2, file_put_contents('oss://0/a.txt', '0a'));
    $this->assertTrue(mkdir('oss://0/1'));
    $this->assertTrue(file_exists('oss://0'));
    $this->assertTrue(file_exists('oss://0/a.txt'));
    $this->assertTrue(file_exists('oss://0/1'));

    try {
      rmdir('oss://0');
      $this->fail('Directory not empty');
    }
    catch (\Exception $e) {}

    unlink('oss://0/a.txt');
    $this->assertTrue(rmdir('oss://0/1'));
    $this->assertTrue(rmdir('oss://0'));
    $this->assertFalse(file_exists('oss://0'));
    $this->assertFalse(file_exists('oss://0/a.txt'));
    $this->assertFalse(file_exists('oss://0/1'));

    // Ensure the directory stat does not exist.
    try {
      stat('oss://0');
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    try {
      opendir('oss://0');
      $this->fail('File not found');
    }
    catch (\Exception $e) {}

    // Ensure the root directory cannot be deleted.
    $this->assertFalse(rmdir('oss://'));
    $this->assertFalse(rmdir('oss:///'));
  }

  /**
   * Tests dirname().
   */
  public function testDirname() {
    $this->assertEquals('oss://', drupal_dirname('oss://'));
    $this->assertEquals('oss://', drupal_dirname('oss://0'));
    $this->assertEquals('oss://', drupal_dirname('oss://0/'));
    $this->assertEquals('oss://0', drupal_dirname('oss://0/1'));
  }

  /**
   * Tests getExternalUrl().
   */
  public function testExternalUrlDefault() {
    $uri = 'oss://abc.jpg';
    $style_uri = 'oss://styles/thumbnail/oss/abc.jpg';
    $this->setConfigData();
    $this->assertEquals('http://test.oss-cn-shenzhen.aliyuncs.com/abc.jpg', file_create_url($uri));
    $this->assertEquals('http://test.oss-cn-shenzhen.aliyuncs.com/abc.jpg?x-oss-process=style/oss_thumb', file_create_url($style_uri));
  }

  /**
   * Tests getExternalUrl() with prefix enabled.
   */
  public function testExternalUrlEnabledPrefix() {
    $uri = 'oss://abc.jpg';
    $style_uri = 'oss://styles/thumbnail/oss/abc.jpg';
    // Set prefix.
    $this->setConfigData([
      'prefix' => 'image',
    ]);
    $this->assertEquals('http://test.oss-cn-shenzhen.aliyuncs.com/image/abc.jpg', file_create_url($uri));
    $this->assertEquals('http://test.oss-cn-shenzhen.aliyuncs.com/image/abc.jpg?x-oss-process=style/oss_thumb', file_create_url($style_uri));
  }

  /**
   * Tests getExternalUrl() with internal enabled.
   */
  public function testExternalUrlEnabledInternal() {
    $uri = 'oss://abc.jpg';
    $style_uri = 'oss://styles/thumbnail/oss/abc.jpg';
    // Set prefix and enable internal.
    $this->setConfigData([
      'prefix' => 'image',
      'internal' => TRUE,
    ]);
    $this->assertEquals('http://test.oss-cn-shenzhen.aliyuncs.com/image/abc.jpg', file_create_url($uri));
    $this->assertEquals('http://test.oss-cn-shenzhen.aliyuncs.com/image/abc.jpg?x-oss-process=style/oss_thumb', file_create_url($style_uri));
  }

  /**
   * Tests getExternalUrl() with CNAME enabled.
   */
  public function testExternalUrlEnabledCNAME() {
    $uri = 'oss://abc.jpg';
    $style_uri = 'oss://styles/thumbnail/oss/abc.jpg';
    // Set prefix and CNAME.
    $this->setConfigData([
      'prefix' => 'image',
      'cname' => 'image.example.com',
    ]);
    $this->assertEquals('http://image.example.com/image/abc.jpg', file_create_url($uri));
    $this->assertEquals('http://image.example.com/image/abc.jpg?x-oss-process=style/oss_thumb', file_create_url($style_uri));
  }

  /**
   * Tests the OSS client API url.
   */
  public function testClientApiUrl() {
    // Disable internal.
    $this->setConfigData([
      'internal' => FALSE,
    ]);
    // Make use of help of the signRtmpUrl method to get a url.
    $url = (new TestOssfsStream())->getClient()->signRtmpUrl('test', 'abc');
    $this->assertStringStartsWith('rtmp://test.oss-cn-shenzhen.aliyuncs.com/', $url);

    // Enable internal.
    $this->setConfigData([
      'internal' => TRUE,
    ]);
    $url = (new TestOssfsStream())->getClient()->signRtmpUrl('test', 'abc');
    $this->assertStringStartsWith('rtmp://test.oss-cn-shenzhen-internal.aliyuncs.com/', $url);

    // Enable internal and CNAME.
    $this->setConfigData([
      'internal' => TRUE,
      'cname' => 'image.example.com',
    ]);
    $url = (new TestOssfsStream())->getClient()->signRtmpUrl('test', 'abc');
    $this->assertStringStartsWith('rtmp://test.oss-cn-shenzhen-internal.aliyuncs.com/', $url);
  }

  protected function setConfigData(array $overrides = []) {
    $data = array_merge([
      'access_key' => 'test_key',
      'secret_key' => 'test_secret',
      'bucket' => 'test',
      'region' => 'oss-cn-shenzhen',
      'cname' => '',
      'prefix' => '',
      'internal' => FALSE,
      'styles' => [
        'thumbnail' => 'oss_thumb',
      ]
    ], $overrides);

    $this->config('ossfs.settings')->setData($data)->save();
  }

}

/**
 * Helper class.
 */
class TestOssfsStream extends OssfsStream {

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return parent::getClient();
  }

}
