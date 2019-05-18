<?php

namespace Drupal\Tests\ossfs\Unit;

use Drupal\ossfs\StreamWrapper\OssfsStream;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Psr7\Stream;

/**
 * @group ossfs
 */
class OssfsGDToolkitTest extends UnitTestCase {

  /**
   * Tests getImageSize().
   */
  public function testGetImageSize() {
    // JPG 89K
    $uri = 'http://e.hiphotos.baidu.com/image/pic/item/42a98226cffc1e174ecc8dfb4390f603728de98d.jpg';
    $imagesize = [
      1000,
      669,
      IMAGETYPE_JPEG,
      'width="1000" height="669"',
      'bits' => 8,
      'channels' => 3,
      'mime' => 'image/jpeg',
    ];
    $this->assertImagesize($uri, $imagesize);

    // JPG 358K
    $uri = 'http://h.hiphotos.baidu.com/image/pic/item/960a304e251f95ca6ecf48a5c3177f3e670952ef.jpg';
    $imagesize = [
      1200,
      800,
      IMAGETYPE_JPEG,
      'width="1200" height="800"',
      'bits' => 8,
      'channels' => 3,
      'mime' => 'image/jpeg',
    ];
    $this->assertImagesize($uri, $imagesize);

    // JPG 376K
    $uri = 'http://c.hiphotos.baidu.com/image/pic/item/4610b912c8fcc3ce110712799845d688d53f20b1.jpg';
    // 10240 bytes is not sufficient for this jpg, it needs 16822 bytes
    $this->assertImagesize($uri, [
      2000,
      1207,
      IMAGETYPE_JPEG,
      'width="2000" height="1207"',
      'bits' => 8,
      'channels' => 3,
      'mime' => 'image/jpeg',
    ]);

    // JPG 477K
    $uri = 'http://c.hiphotos.baidu.com/image/pic/item/7a899e510fb30f24706975c9c195d143ac4b030c.jpg';
    $imagesize = [
      1280,
      1921,
      IMAGETYPE_JPEG,
      'width="1280" height="1921"',
      'bits' => 8,
      'channels' => 3,
      'mime' => 'image/jpeg',
    ];
    $this->assertImagesize($uri, $imagesize);

    // GIF 4.8M
    $uri = 'https://wx1.sinaimg.cn/mw690/6b18d922gy1fh8aj5mzvag207e05knpg.gif';
    $imagesize = [
      266,
      200,
      IMAGETYPE_GIF,
      'width="266" height="200"',
      'bits' => 7,
      'channels' => 3,
      'mime' => 'image/gif',
    ];
    $this->assertImagesize($uri, $imagesize);

    // GIF 784K (extension is jpg, but the file is actually a gif)
    $uri = 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1510295684816&di=cafc32958dcd723415d46a3799f7d8c8&imgtype=0&src=http%3A%2F%2Fimg.mp.itc.cn%2Fupload%2F20160416%2F284614cfe4c844148001c338e3f50ff3.jpg';
    $imagesize = [
      400,
      216,
      IMAGETYPE_GIF,
      'width="400" height="216"',
      'bits' => 7,
      'channels' => 3,
      'mime' => 'image/gif',
    ];
    $this->assertImagesize($uri, $imagesize);

    // PNG 769K
    $uri = 'http://www.htjz520.com/uploads/ueditor/php/upload/image/20160712/1468310514949541.png';
    $imagesize = [
      1200,
      599,
      IMAGETYPE_PNG,
      'width="1200" height="599"',
      'bits' => 8,
      // 'channels' => 3,
      'mime' => 'image/png',
    ];
    $this->assertImagesize($uri, $imagesize);

    // PNG 2.7M
    $uri = 'http://att.bbs.duowan.com/forum/201609/21/012629nnvrvk4jrwk04rln.png';
    $imagesize = [
      1920,
      1080,
      IMAGETYPE_PNG,
      'width="1920" height="1080"',
      'bits' => 8,
      // 'channels' => 3,
      'mime' => 'image/png',
    ];
    $this->assertImagesize($uri, $imagesize);
  }

  private function assertImagesize($uri, $expected) {
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_RANGE, '0-32767'); // read 32768 bytes
    $raw = curl_exec($ch);

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->assertTrue(206 === $status);

    $handle = fopen('php://temp', 'r+b');
    $stream = new Stream($handle);
    $stream->write($raw);
    $stream->seek(0);

    $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    $this->assertEquals($expected, OssfsStream::getImagesize($stream, $extension));
    $this->assertNotEquals(0, $stream->tell());

    curl_close($ch);
    $stream->close();
  }

}
